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
        $srcRoot = $repoRoot . $ds . 'upload' . $ds . 'src' . $ds . 'addons' . $ds . $addOn->prepareAddOnIdForPath();

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