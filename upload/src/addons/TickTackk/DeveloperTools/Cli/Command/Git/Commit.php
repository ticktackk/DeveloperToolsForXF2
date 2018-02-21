<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use TickTackk\DeveloperTools\Git\GitException;
use XF\App;
use XF\Cli\Command\AddOnActionTrait;
use \XF\Util\File;
use TickTackk\DeveloperTools\Git\GitRepository;

class Commit extends Command
{
    use AddOnActionTrait;

    protected function configure()
    {
        $this
            ->setName('ticktackk-devtools:git-commit')
            ->setDescription('Copies changes made to the add-on to repository and then finally commits the changes')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            )
            ->addOption(
                'message',
                null,
                InputOption::VALUE_OPTIONAL,
                'Commit message'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $addOnId = $input->getArgument('id');
        $addOn = $this->checkEditableAddOn($addOnId, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $addOnEntity = \XF::app()->em()->findOne('XF:AddOn', ['addon_id' => $addOnId]);

        $commitMessage = $input->getOption('message');
        if (!$commitMessage)
        {
            $question = new Question("<question>Commit summary:</question> ");
            $commitMessage = $helper->ask($input, $output, $question);
            $output->writeln("");
        }

        $addOnDirectory = $addOn->getAddOnDirectory();
        $ds = DIRECTORY_SEPARATOR;
        $repoRoot = $addOnDirectory . $ds . '_repo';
        $uploadDirectory = $repoRoot . $ds . 'upload';

        $git = new GitRepository($repoRoot);
        if (!$git->isInitialized())
        {
            $output->writeln(["", "Git directory must be initialized"]);
            return 0;
        }

        $options = \XF::options();
        $gitUsername = $options->developerTools_git_username;
        $gitEmail = $options->developerTools_git_email;

        if (!empty($git->config()->get('user.name')->execute()))
        {
            $git->config()->add('user.name', $gitUsername)->execute();
        }
        if (!empty($git->config()->get('user.email')->execute()))
        {
            $git->config()->add('user.email', $gitEmail)->execute();
        }

        if (empty($git->config()->get('user.name')->execute()) || empty($git->config()->get('user.email')->execute()))
        {
            $output->writeln(["", "Git username or email cannot be empty."]);
            return 1;
        }

        if (is_dir($repoRoot))
        {
            if (file_exists($uploadDirectory))
            {
                File::deleteDirectory($uploadDirectory);
            }

            foreach ($this->getFileIterator($repoRoot) as $file)
            {
                $path = $this->standardizePath($repoRoot, $file->getPathname());

                if (strpos($path, '.git') === 0)
                {
                    continue;
                }

                unlink($file->getRealPath());
            }
        }

        $uploadRoot = $repoRoot . $ds . 'upload';
        $srcRoot =  $uploadRoot . $ds . 'src' . $ds . 'addons' . $ds . $addOn->prepareAddOnIdForPath();

        $filesIterator = $this->getFileIterator($addOnDirectory);
        foreach ($filesIterator AS $file)
        {
            $path = $this->standardizePath($addOnDirectory, $file->getPathname());
            if ($this->isPartOfExcludedDirectoryForRepo($path))
            {
                continue;
            }

            if (!$file->isDir())
            {
                File::copyFile($file->getPathname(), $srcRoot . $ds . $path, false);
            }
        }

        if (!empty($addOnEntity->license))
        {
            $licenseFileInRepoRoot = $repoRoot . $ds . 'LICENSE';
            if (file_exists($licenseFileInRepoRoot) && is_readable($licenseFileInRepoRoot))
            {
                unlink($licenseFileInRepoRoot);
            }

            $licenseContent = <<< LICENSE
{$addOnEntity->license}
LICENSE;
            File::writeFile($repoRoot . $ds . 'LICENSE.md', $licenseContent, false);
        }

        $globalGitIgnore = \XF::app()->options()->developerTools_git_ignore;

        if (!empty($globalGitIgnore))
        {
            $globalGitIgnoreContent = <<< GLOBALGITIGNORE
{$addOnEntity->gitignore}
GLOBALGITIGNORE;
            File::writeFile($repoRoot . $ds . '.gitignore', $globalGitIgnoreContent, false);
        }

        if (!empty($addOnEntity->gitignore))
        {
            $gitIgnoreContent = <<< GITIGNORE
{$addOnEntity->gitignore}
GITIGNORE;
            File::writeFile($srcRoot . $ds . '.gitignore', $gitIgnoreContent, false);
        }

        if (!empty($addOnEntity->readme_md))
        {
            $readMeMarkdownFileInRepoRoot = $repoRoot . $ds . 'README.md';
            if (file_exists($readMeMarkdownFileInRepoRoot) && is_readable($readMeMarkdownFileInRepoRoot))
            {
                unlink($readMeMarkdownFileInRepoRoot);
            }

            $readMeMarkdownContent = <<< README_MD
{$addOnEntity->readme_md}
README_MD;
            File::writeFile($repoRoot . $ds . 'README.md', $readMeMarkdownContent, false);
        }

        $git->add()->execute('*');

        if (empty($git->status()->getIndexStatus()))
        {
            $output->writeln(["", "Nothing to commit."]);
            return 0;
        }

        $git->commit()->message($commitMessage)->execute();// do error handling here

        $output->writeln(["", "Successfully committed changes."]);
        return 0;
    }

    protected function isPartOfExcludedDirectoryForRepo($path)
    {
        foreach ($this->getExcludedDirectoriesForRepo() AS $dir)
        {
            if (strpos($path, $dir) === 0)
            {
                return true;
            }
        }
        return false;
    }

    protected function getExcludedDirectoriesForRepo()
    {
        return [
            '_build',
            '_releases',
            '_repo'
        ];
    }

    /**
     * @param $path
     *
     * @return \SplFileInfo[]|\RecursiveIteratorIterator
     */
    protected function getFileIterator($path)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path, \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    protected function standardizePath($rootPath, $path)
    {
        $ds = DIRECTORY_SEPARATOR;
        return preg_replace('#^' . preg_quote(rtrim($rootPath, $ds) . $ds) . '#', '', $path, 1);
    }
}