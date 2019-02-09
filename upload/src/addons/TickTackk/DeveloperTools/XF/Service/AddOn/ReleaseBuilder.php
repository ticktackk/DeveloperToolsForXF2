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
    protected function prepareFilesToCopy() : void
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
     * @return array
     */
    protected function getExcludedDirectories() : array
    {
        return array_merge([
            '_repo',
            '_tests'
        ], parent::getExcludedDirectories());
    }

    /**
     * @param $fileName
     *
     * @return bool
     */
    protected function isExcludedFileName($fileName) : bool
    {
        if (\in_array($fileName, ['git.json', 'dev.json']))
        {
            return true;
        }

        return parent::isExcludedFileName($fileName);
    }
}