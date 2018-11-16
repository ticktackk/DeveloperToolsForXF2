<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Bit3\GitPhp\GitException;
use Bit3\GitPhp\GitRepository;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File;

/**
 * Class Commit
 *
 * @package TickTackk\DeveloperTools
 */
class Commit extends Command
{
    use AddOnActionTrait;

    protected function configure() : void
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
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) : ? int
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

        /** @var \TickTackk\DeveloperTools\XF\Entity\AddOn $addOnEntity */
        $addOnEntity = $addOn->getInstalledAddOn();

        $addOnDirectory = $addOn->getAddOnDirectory();
        $ds = DIRECTORY_SEPARATOR;

        $repoRoot = $addOnDirectory . $ds . '_repo';

        $git = new GitRepository($repoRoot);
        if (!$git->isInitialized())
        {
            $command = $this->getApplication()->find('ticktackk-devtools:git-init');
            $childInput = new ArrayInput([
                'command' => 'ticktackk-devtools:git-init',
                'id' => $addOn->getAddOnId()
            ]);
            $command->run($childInput, $output);
        }

        try
        {
            $customSubDir = $git->config()->get('custom.subdir')->execute();
            if (!empty($customSubDir))
            {
                $repoRoot .= ($ds . $customSubDir);
            }
        }
        catch (GitException $e)
        {
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
        $developerOptions = $addOnEntity->DeveloperOptions;

        if (!empty($developerOptions['parse_additional_files']))
        {
            /** @noinspection PhpUndefinedFieldInspection */
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
                } else
                {
                    $stdPath = $this->standardizePath($root, $filePath);
                    File::copyFile($filePath, $uploadRoot . $ds . $stdPath, false);
                }
            }
        }

        if (!empty($developerOptions['license']))
        {
            $licenseFileInRepoRoot = $repoRoot . $ds . 'LICENSE.md';
            if (file_exists($licenseFileInRepoRoot) && is_readable($licenseFileInRepoRoot))
            {
                unlink($licenseFileInRepoRoot);
            }

            File::writeFile($repoRoot . $ds . 'LICENSE.md', $developerOptions['license'], false);
        }

        if (!empty($developerOptions['readme']))
        {
            $readMeMarkdownFileInRepoRoot = $repoRoot . $ds . 'README.md';
            if (file_exists($readMeMarkdownFileInRepoRoot) && is_readable($readMeMarkdownFileInRepoRoot))
            {
                unlink($readMeMarkdownFileInRepoRoot);
            }

            File::writeFile($readMeMarkdownFileInRepoRoot, $developerOptions['readme'], false);
        }

        $globalGitIgnore = \XF::app()->options()->developerTools_git_ignore;
        $globalGitIgnore = explode("\n", $globalGitIgnore);
        $addOnGitIgnore = !empty($developerOptions['gitignore']) ? explode("\n", $developerOptions['gitignore']) : [];
        $gitIgnoreLines = array_unique(array_merge($globalGitIgnore, $addOnGitIgnore));

        File::writeFile($repoRoot . $ds . '.gitignore', implode("\n", $gitIgnoreLines), false);

        $git->rm()->cached()->force()->execute('*');
        $git->add()->execute('*');

        $gitConfigurations = $addOnEntity->GitConfigurations;
        if (empty($gitConfigurations['name']) || empty($gitConfigurations['email']))
        {
            $options = \XF::app()->options();
            $globalGitName = $options->developerTools_git_username;
            $globalGitEmail = $options->developerTools_git_email;

            if (!empty($globalGitName) && !empty($globalGitEmail))
            {
                $gitConfigurations['name'] = $options->developerTools_git_username;
                $gitConfigurations['email'] = $options->developerTools_git_email;
            }
            else
            {
                $output->writeln(['', 'No git username or email specified for ' . $addOnEntity->title]);
                return 1;
            }
        }

        try
        {
            $existingGitName = $git->config()->get('user.name')->execute();
            if ($existingGitName !== $gitConfigurations['name'])
            {
                $git->config()->replaceAll('user.name', $gitConfigurations['name']);
            }
        }
        catch (GitException $e)
        {
            $git->config()->add('user.name', $gitConfigurations['name'])->execute();
        }

        try
        {
            $existingGitEmail = $git->config()->get('user.email')->execute();
            if ($existingGitEmail !== $gitConfigurations['email'])
            {
                $git->config()->replaceAll('user.email', $gitConfigurations['email']);
            }
        }
        catch (GitException $e)
        {
            $git->config()->add('user.email', $gitConfigurations['email'])->execute();
        }

        try
        {
            if (empty($git->config()->get('user.name')->execute()) || empty($git->config()->get('user.email')->execute()))
            {
                $output->writeln(['', 'Git username or email cannot be empty.']);
                return 1;
            }
        }
        catch (GitException $e)
        {
            $output->writeln(['', $e->getMessage()]);
            return 1;
        }

        if (empty($git->status()->getIndexStatus()))
        {
            $output->writeln(['', 'Nothing to commit.']);
            return 0;
        }

        $commitMessage = $input->getOption('message');
        if (!$commitMessage)
        {
            $question = new Question('<question>Commit summary:</question>');
            $commitMessage = $helper->ask($input, $output, $question);
            $output->writeln('');
        }

        try
        {
            $git->commit()->message($commitMessage)->execute();
        }
        catch (GitException $e)
        {
            $output->writeln(['', $e->getMessage()]);
            return 1;
        }

        $output->writeln(['', 'Successfully committed changes.']);
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

    /**
     * @param $rootPath
     * @param $path
     *
     * @return null|string|string[]
     */
    protected function standardizePath($rootPath, $path)
    {
        $ds = DIRECTORY_SEPARATOR;
        /** @noinspection PregQuoteUsageInspection */
        return preg_replace('#^' . preg_quote(rtrim($rootPath, $ds) . $ds) . '#', '', $path, 1);
    }

    /**
     * @param $path
     *
     * @return bool
     */
    protected function isPartOfExcludedDirectoryForRepo($path) : bool
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

    /**
     * @return array[]|false|string[]
     */
    protected function getExcludedDirectoriesForRepo()
    {
        return preg_split('/\r?\n/', \XF::options()->developerTools_excluded_directories, -1, PREG_SPLIT_NO_EMPTY);
    }
}