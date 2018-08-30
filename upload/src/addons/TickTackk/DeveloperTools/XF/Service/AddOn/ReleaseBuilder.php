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
        /** @var \TickTackk\DeveloperTools\XF\Entity\AddOn $addOnEntity */
        $addOnEntity = $this->addOn->getInstalledAddOn();

        if ($addOnEntity)
        {
            $developerOptions = $addOnEntity->DeveloperOptions;
            if (!empty($developerOptions['license']))
            {
                File::writeFile($buildUploadRoot . $ds . 'LICENSE.md', $developerOptions['license'], false);
            }

            if (!empty($developerOptions['readme']))
            {
                File::writeFile($buildUploadRoot . $ds . 'README.md', $developerOptions['readme'], false);
            }

            if (file_exists($buildUploadRoot . $ds . 'dev.json'))
            {
                unlink($buildUploadRoot . $ds . 'dev.json');
            }
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
            '_repo',
            '_tests'
        ], parent::getExcludedDirectories());
    }
}