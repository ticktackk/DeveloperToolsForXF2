<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use TickTackk\DeveloperTools\Service\AddOn\ReadmeBuilder as AddOnReadmeBuilder;
use XF\PrintableException;
use XF\Service\AbstractService;
use XF\Util\File as FileUtil;

/**
 * Class ReleaseBuilder
 *
 * @package TickTackk\DeveloperTools
 */
class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    /**
     * @throws PrintableException
     */
    protected function prepareDirectories()
    {
        $readmeBuilderSvc = $this->getReadmeBuilderSvc();
        if (!$readmeBuilderSvc->validate($errors))
        {
            throw new PrintableException($errors);
        }
        $readmeBuilderSvc->save();

        parent::prepareDirectories();
    }

    /**
     * @throws \XF\PrintableException
     */
    public function performBuildTasks()
    {
        parent::performBuildTasks();

        $addOn = $this->addOn;
        $buildJson = $addOn->getBuildJson();

        if ($this->buildTasksComplete)
        {
            $excludeFiles = $buildJson['exclude_files'] ?? [];
            $excludeFiles = (array) $excludeFiles;
            \array_push($excludeFiles, ...['git.json', 'dev.json']);

            $this->excludeFiles($excludeFiles);
        }
    }

    /**
     * @param array $excludedFiles
     */
    protected function excludeFiles(array $excludedFiles) : void
    {
        $addOnBase = $this->addOnBase;

        foreach ($excludedFiles AS $excludedFile)
        {
            $filePath = FileUtil::canonicalizePath($excludedFile, $addOnBase);
            if (\file_exists($filePath))
            {
                \unlink($filePath);
            }
        }
    }

    protected function prepareFilesToCopy()
    {
        parent::prepareFilesToCopy();

        foreach (['LICENSE', "README", 'CHANGELOG'] AS $fileName)
        {
            $this->copyFileToBuildRoot($fileName, ['md', '', 'txt', 'html']);
        }
    }

    /**
     * @param array|string $possibleFileName
     * @param array|string $possibleExtensions
     */
    protected function copyFileToBuildRoot($possibleFileName, $possibleExtensions) : void
    {
        $addOnRoot = $this->addOnRoot;
        $buildRoot = $this->buildRoot;

        foreach ((array) $possibleFileName AS $fileName)
        {
            foreach ((array) $possibleExtensions AS $possibleExtension)
            {
                $possibleFileNameFinal = $fileName;
                if (!empty($possibleExtension))
                {
                    $possibleFileNameFinal .= '.' . $possibleExtension;
                }

                $possibleFilePath = FileUtil::canonicalizePath($possibleFileNameFinal, $buildRoot);
                if (\file_exists($possibleFilePath))
                {
                    return;
                }
            }
        }

        foreach ((array) $possibleFileName AS $fileName)
        {
            foreach ((array) $possibleExtensions AS $possibleExtension)
            {
                $possibleFileNameFinal = $fileName;
                if (!empty($possibleExtension))
                {
                    $possibleFileNameFinal .= '.' . $possibleExtension;
                }

                $filePath = FileUtil::canonicalizePath($possibleFileNameFinal, $addOnRoot);
                if (\file_exists($filePath) && \is_readable($filePath))
                {
                    $destinationPath = FileUtil::canonicalizePath($possibleFileNameFinal, $buildRoot);
                    FileUtil::copyFile($filePath, $destinationPath);
                    return;
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getExcludedDirectories()
    {
        $addOn = $this->addOn;
        $buildJson = $addOn->getBuildJson();
        $excludedDirectories = parent::getExcludedDirectories();

        $excludedDirectoriesFromBuildFile = (array) ($buildJson['exclude_directories'] ?? []);
        \array_push($excludedDirectoriesFromBuildFile, ...['_repo', '_tests', '_dev']);
        \array_push($excludedDirectories, ...$excludedDirectoriesFromBuildFile);

        return \array_unique($excludedDirectories);
    }

    /**
     * @return AbstractService|AddOnReadmeBuilder
     */
    protected function getReadmeBuilderSvc() : AddOnReadmeBuilder
    {
        $addOn = $this->addOn;
        return $this->service('TickTackk\DeveloperTools:AddOn\ReadmeBuilder', $addOn);
    }
}