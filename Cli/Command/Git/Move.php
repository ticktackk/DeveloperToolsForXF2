<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bit3\GitPhp\GitException;
use Bit3\GitPhp\GitRepository;
use XF\Util\File as FileUtil;
use XF\Cli\Command\AddOnActionTrait;
use TickTackk\DeveloperTools\Cli\Command\DevToolsActionTrait;
use TickTackk\DeveloperTools\XF\Entity\AddOn as ExtendedAddOnEntity;

/**
 * Class Move
 *
 * @package TickTackk\DeveloperTools\Cli\Command\Git
 */
class Move extends Command
{
    use AddOnActionTrait;
    use DevToolsActionTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this
            ->setName('ticktackk-devtools:git-move')
            ->setDescription('Copies changes made to the add-on to repository')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $addOnId = $input->getArgument('id');
        $addOn = $this->checkEditableAddOn($addOnId, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        /** @var ExtendedAddOnEntity $addOnEntity */
        $addOnEntity = $addOn->getInstalledAddOn();

        $addOnDirectory = $addOn->getAddOnDirectory();

        $developerOptions = $addOnEntity->DeveloperOptions;
        $gitConfigurations = $addOnEntity->GitConfigurations;

        $repoRoot = $this->getAddOnRepoDir($addOn);

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

        $globalGitIgnore = \XF::app()->options()->developerTools_git_ignore;
        $globalGitIgnore = \explode("\n", $globalGitIgnore);

        $addOnGitIgnore = $developerOptions['gitignore'] ?? [];
        if (\is_string($addOnGitIgnore))
        {
            $addOnGitIgnore = \explode("\n", $addOnGitIgnore);
        }

        $gitIgnoreLines = $globalGitIgnore + $addOnGitIgnore;
        $gitIgnoreLines = \array_unique($gitIgnoreLines);

        $gitIgnorePath = FileUtil::canonicalizePath('.gitignore', $repoRoot);
        FileUtil::writeFile($gitIgnorePath, \implode("\n", $gitIgnoreLines), false);

        try
        {
            if (!empty($gitConfigurations['custom_subdir']))
            {
                $repoRoot = FileUtil::canonicalizePath($repoRoot, $gitConfigurations['custom_subdir']);
            }
        }
        catch (GitException $e)
        {
        }

        $uploadDirectory = FileUtil::canonicalizePath('upload', $repoRoot);

        if (\is_dir($repoRoot))
        {
            if (\file_exists($uploadDirectory))
            {
                FileUtil::deleteDirectory($uploadDirectory);
            }

            foreach ($this->getFileIterator($repoRoot) as $file)
            {
                $path = FileUtil::canonicalizePath($file->getPathname(), $repoRoot);

                if (\strpos($path, '.git') === 0)
                {
                    continue;
                }

                if ($file->isDir())
                {
                    FileUtil::deleteDirectory($file->getRealPath());
                } else
                {
                    \unlink($file->getRealPath());
                }
            }
        }

        $uploadRoot = FileUtil::canonicalizePath('upload', $repoRoot);
        $ds = \XF::$DS;
        $addOnIdForPath = $addOn->prepareAddOnIdForPath();
        $srcRoot = FileUtil::canonicalizePath("src{$ds}addons{$ds}{$addOnIdForPath}", $uploadRoot);
        $rootPath = \XF::getRootDirectory();
        $filesRoot = $addOn->getFilesDirectory();

        $filesIterator = $this->getFileIterator($addOnDirectory);
        foreach ($filesIterator AS $file)
        {
            $path = FileUtil::canonicalizePath($addOnDirectory, $file->getPathname());
            if ($this->isPartOfExcludedDirectoryForRepo($path))
            {
                continue;
            }

            if (!empty($developerOptions['parse_additional_files']) && \strpos($path, '_no_upload') === 0)
            {
                $noUploadPath = FileUtil::canonicalizePath('_no_upload', $path);

                if (!$file->isDir())
                {
                    $copyRoot = \dirname($uploadRoot, 1); // These need copying, but to a different path (outside upload)
                    $destinationPath = FileUtil::canonicalizePath($noUploadPath, $copyRoot);
                    FileUtil::copyFile($file->getPathname(), $destinationPath, false);
                }
            }

            if (!$file->isDir())
            {
                $destinationPath = FileUtil::canonicalizePath($path, $srcRoot);
                FileUtil::copyFile($file->getPathname(), $destinationPath, false);
            }
        }

        if (!empty($developerOptions['parse_additional_files']))
        {
            /** @noinspection PhpUndefinedFieldInspection */
            $additionalFiles = $addOn->additional_files;
            foreach ((array) $additionalFiles AS $additionalFile)
            {
                $filePath = FileUtil::canonicalizePath($additionalFile, $filesRoot);
                if (\file_exists($filePath))
                {
                    $root = $filesRoot;
                } else
                {
                    $filePath = FileUtil::canonicalizePath($additionalFile, $rootPath);
                    if (!\file_exists($filePath))
                    {
                        continue;
                    }
                    $root = $rootPath;
                }

                if (\is_dir($filePath))
                {
                    $filesIterator = $this->getFileIterator($filePath);
                    foreach ($filesIterator AS $file)
                    {
                        $stdPath = FileUtil::canonicalizePath($file->getPathname(), $root);
                        if (!$file->isDir())
                        {
                            $destinationPath = FileUtil::canonicalizePath($stdPath, $uploadRoot);
                            FileUtil::copyFile($file->getPathname(), $destinationPath, false);
                        }
                    }
                } else
                {
                    $stdPath = FileUtil::canonicalizePath($filePath, $root);
                    $destinationPath = FileUtil::canonicalizePath($stdPath, $uploadRoot);

                    FileUtil::copyFile($filePath, $destinationPath, false);
                }
            }
        }

        if (!empty($developerOptions['license']))
        {
            $licenseFileInRepoRoot = FileUtil::canonicalizePath('LICENSE.md', $repoRoot);
            if (\file_exists($licenseFileInRepoRoot) && \is_readable($licenseFileInRepoRoot))
            {
                \unlink($licenseFileInRepoRoot);
            }

            FileUtil::writeFile($licenseFileInRepoRoot, $developerOptions['license'], false);
        }

        if (!empty($developerOptions['readme']))
        {
            $readMeMarkdownFileInRepoRoot = FileUtil::canonicalizePath('README.md', $repoRoot);
            if (\file_exists($readMeMarkdownFileInRepoRoot) && \is_readable($readMeMarkdownFileInRepoRoot))
            {
                \unlink($readMeMarkdownFileInRepoRoot);
            }

            FileUtil::writeFile($readMeMarkdownFileInRepoRoot, $developerOptions['readme'], false);
        }

        $git->add()->execute('*');

        if (empty($gitConfigurations['name']) || empty($gitConfigurations['email']))
        {
            $options = \XF::app()->options();
            $globalGitName = $options->developerTools_git_username;
            $globalGitEmail = $options->developerTools_git_email;

            if (!empty($globalGitName) && !empty($globalGitEmail))
            {
                $gitConfigurations['name'] = $options->developerTools_git_username;
                $gitConfigurations['email'] = $options->developerTools_git_email;
            } else
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

        $output->writeln(['', 'Successfully copied files.']);
        return 0;
    }

    /**
     * @param string $path Path for which file iterator must be returned
     *
     * @return \RecursiveIteratorIterator filter iterator for path provided
     */
    protected function getFileIterator($path) : \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path, \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * @param string $path Path of directory to check again
     *
     * @return bool Returns true if path is excluded
     */
    protected function isPartOfExcludedDirectoryForRepo($path) : bool
    {
        foreach ($this->getExcludedDirectoriesForRepo() AS $dir)
        {
            if (\strpos($path, $dir) === 0)
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