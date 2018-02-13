<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use XF\Util\File;
use TickTackk\DeveloperTools\Git\GitRepository;

class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    protected $repoRoot;

    protected function prepareDirectories()
    {
        parent::prepareDirectories();

        $addOn = $this->addOn;
        $addOnDir = $addOn->getAddOnDirectory();

        $ds = DIRECTORY_SEPARATOR;

        $repoDir = $addOnDir . $ds . '_repo';
        $uploadDir = $repoDir . $ds . 'upload';

        if (is_dir($uploadDir))
        {
            File::deleteDirectory($uploadDir);
        }
        File::createDirectory($repoDir, false);

        $this->repoRoot = $repoDir;
    }

    protected function prepareFilesToCopy()
    {
        parent::prepareFilesToCopy();
        $this->prepareFilesToCopyForRepo();
    }

    protected function prepareFilesToCopyForRepo()
    {
        $addOnRoot = $this->addOnRoot;
        $repoRoot = $this->repoRoot;

        $addOn = $this->addOn;
        $ds = DIRECTORY_SEPARATOR;
        $uploadRoot = $repoRoot . $ds . 'upload';
        $srcRoot =  $uploadRoot . $ds . 'src' . $ds . 'addons' . $ds . $addOn->prepareAddOnIdForPath();

        $filesIterator = $this->getFileIterator($addOnRoot);
        foreach ($filesIterator AS $file)
        {
            $path = $this->standardizePath($addOnRoot, $file->getPathname());
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

        $addOnId = $this->addOn->getAddOnId();
        $addOn = $this->em()->findOne('XF:AddOn', ['addon_id' => $addOnId]);

        if (!empty($addOn->license))
        {
            $licenseContent = <<< LICENSE
{$addOn->license}
LICENSE;
            File::writeFile($srcRoot . $ds . 'LICENSE', $licenseContent, false);
        }

        if (!empty($addOn->gitignore))
        {
            $gitIgnoreContent = <<< GITIGNORE
{$addOn->gitignore}
GITIGNORE;
            File::writeFile($srcRoot . $ds . '.gitignore', $gitIgnoreContent, false);
        }

        if (!empty($addOn->readme_md))
        {
            $readMeMarkdownFileInRepoRoot = $repoRoot . $ds . 'README.md';
            if (file_exists($readMeMarkdownFileInRepoRoot) && is_readable($readMeMarkdownFileInRepoRoot))
            {
                unlink($readMeMarkdownFileInRepoRoot);
            }

            $readMeMarkdownContent = <<< README_MD
{$addOn->readme_md}
README_MD;
            File::writeFile($srcRoot . $ds . 'README.md', $readMeMarkdownContent, false);
        }

        $git = new GitRepository($repoRoot);
        $git->init()->execute();
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
}