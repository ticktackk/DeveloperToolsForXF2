<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use TickTackk\DeveloperTools\Git\GitRepository;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File;

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
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
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

        $addOnEntity = $addOn->getInstalledAddOn();

        $addOnDirectory = $addOn->getAddOnDirectory();
        $ds = DIRECTORY_SEPARATOR;
        $repoRoot = $addOnDirectory . $ds . '_repo';

        $git = new GitRepository($repoRoot);
        if (!$git->isInitialized())
        {
            $output->writeln(["", "Git directory must be initialized"]);
            return 0;
        }

        $options = \XF::options();
        $gitUsername = $options->developerTools_git_username;
        $gitEmail = $options->developerTools_git_email;

        if (empty($git->config()->get('user.name')->execute()))
        {
            $git->config()->add('user.name', $gitUsername)->execute();
        }
        if (empty($git->config()->get('user.email')->execute()))
        {
            $git->config()->add('user.email', $gitEmail)->execute();
        }

        if (empty($git->config()->get('user.name')->execute()) || empty($git->config()->get('user.email')->execute()))
        {
            $output->writeln(["", "Git username or email cannot be empty."]);
            return 1;
        }

        $globalGitIgnore = \XF::app()->options()->developerTools_git_ignore;

        if (!empty($globalGitIgnore))
        {
            File::writeFile($repoRoot . $ds . '.gitignore', $globalGitIgnore, false);
        }

        $customSubDir = $git->config()->get('custom.subdir')->execute();
        if (!empty($customSubDir))
        {
            $repoRoot .= ($ds . $customSubDir);
        }
        $uploadDirectory = $repoRoot . $ds . 'upload';

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
        $srcRoot = $uploadRoot . $ds . 'src' . $ds . 'addons' . $ds . $addOn->prepareAddOnIdForPath();

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

        $rootPath = \XF::getRootDirectory();
        $filesRoot = $addOn->getFilesDirectory();

        if ($addOnEntity->devTools_parse_additional_files)
        {
            $additionalFiles = $addOn->additional_files;
            foreach ((array)$additionalFiles AS $additionalFile)
            {
                $filePath = $filesRoot . $ds . $additionalFile;
                if (file_exists($filePath))
                {
                    $root = $filesRoot;
                }
                else
                {
                    $filePath = $rootPath . $ds . $additionalFile;
                    if (!file_exists($filePath))
                    {
                        continue;
                    }
                    $root = $rootPath;
                }

                if (is_dir($filePath))
                {
                    $filesIterator = $this->getFileIterator($filePath);
                    foreach ($filesIterator AS $file)
                    {
                        $stdPath = $this->standardizePath($root, $file->getPathname());
                        if (!$file->isDir())
                        {
                            File::copyFile($file->getPathname(), $uploadRoot . $ds . $stdPath, false);
                        }
                    }
                }
                else
                {
                    $stdPath = $this->standardizePath($root, $filePath);
                    File::copyFile($filePath, $uploadRoot . $ds . $stdPath, false);
                }
            }
        }

        if (!empty($addOnEntity->devTools_license))
        {
            File::writeFile($repoRoot . $ds . 'LICENSE.md', $addOnEntity->devTools_license, false);
        }

        if (!empty($addOnEntity->devTools_gitignore))
        {
            File::writeFile($srcRoot . $ds . '.gitignore', $addOnEntity->gitignore, false);
        }

        if (!empty($addOnEntity->devTools_readme_md))
        {
            $readMeMarkdownFileInRepoRoot = $repoRoot . $ds . 'README.md';
            if (file_exists($readMeMarkdownFileInRepoRoot) && is_readable($readMeMarkdownFileInRepoRoot))
            {
                unlink($readMeMarkdownFileInRepoRoot);
            }

            File::writeFile($repoRoot . $ds . 'README.md', $addOnEntity->devTools_readme_md, false);
        }

        $git->add()->execute('*');

        if (empty($git->status()->getIndexStatus()))
        {
            $output->writeln(["", "Nothing to commit."]);
            return 0;
        }

        $commitMessage = $input->getOption('message');
        if (!$commitMessage)
        {
            $question = new Question("<question>Commit summary:</question> ");
            $commitMessage = $helper->ask($input, $output, $question);
            $output->writeln("");
        }

        $git->commit()->message($commitMessage)->execute();// do error handling here

        $output->writeln(["", "Successfully committed changes."]);
        return 0;
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
        return preg_split('/\r?\n/', \XF::options()->developerTools_excluded_directories, -1, PREG_SPLIT_NO_EMPTY);
    }
}