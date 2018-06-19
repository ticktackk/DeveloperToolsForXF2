<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use XF\Util\File;

/**
 * Class ReleaseBuilder
 *
 * @package TickTackk\DeveloperTools
 */
class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    protected function prepareFilesToCopy()
    {
        parent::prepareFilesToCopy();

        $addOn = $this->addOn;
        $ds = DIRECTORY_SEPARATOR;
        $buildUploadRoot = $addOn->getBuildDirectory();
        $addOnEntity = $this->addOn->getInstalledAddOn();

        if (!empty($addOnEntity->devTools_license))
        {
            File::writeFile($buildUploadRoot . $ds . 'LICENSE.md', $addOnEntity->devTools_license, false);
        }

        if (!empty($addOnEntity->devTools_readme_md))
        {
            File::writeFile($buildUploadRoot . $ds . 'README.md', $addOnEntity->devTools_readme_md, false);
        }
    }

    /**
     * @throws \XF\PrintableException
     */
    public function performBuildTasks()
    {
        parent::performBuildTasks();

        $dataPath = $this->addOnRoot . DIRECTORY_SEPARATOR . '_data';
        if (is_dir($dataPath))
        {
            File::deleteDirectory($dataPath);
        }
    }

    /**
     * @return array
     */
    protected function getExcludedDirectories()
    {
        return array_merge([
            '_repo'
        ], parent::getExcludedDirectories());
    }
}