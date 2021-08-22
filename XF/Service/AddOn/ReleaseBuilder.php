<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use XF\Service\AbstractService;
use XF\Service\AddOn\Exporter as AddOnExporterSvc;
use XF\Util\File as FileUtil;

/**
 * @version 1.3.6
 */
class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    /**
     * @since 1.3.6
     */
    protected function prepareDataDirectory() : void
    {
        $addOnExporterSvc = $this->getAddonExporterSvc();

        foreach ($addOnExporterSvc->getContainers() AS $containerName)
        {
            $addOnExporterSvc->export($containerName);
        }
    }

    /**
     * @version 1.3.6
     */
    protected function prepareFilesToCopy()
    {
        $this->prepareDataDirectory();

        parent::prepareFilesToCopy();

        foreach (['LICENSE', "README", 'CHANGELOG'] AS $fileName)
        {
            $this->copyFileToBuildRoot($fileName, ['md', '', 'txt', 'html']);
        }
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

                if (empty($possibleFileNameFinal))
                {
                    continue;
                }

                $filePath = FileUtil::canonicalizePath($possibleFileNameFinal, $addOnRoot);
                if (\file_exists($filePath) && \is_readable($filePath) && \is_file($filePath))
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
        \array_push($excludedDirectoriesFromBuildFile, ...['_repo', '_tests', '_dev', '.idea']);
        \array_push($excludedDirectories, ...$excludedDirectoriesFromBuildFile);

        return \array_unique($excludedDirectories);
    }

    /**
     * @since 1.3.6
     *
     * @return AbstractService|AddOnExporterSvc
     */
    protected function getAddonExporterSvc() : AddOnExporterSvc
    {
        return $this->service('XF:AddOn\Exporter', $this->addOn);
    }
}