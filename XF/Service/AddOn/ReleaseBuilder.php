<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use XF\Util\File as FileUtil;
use TickTackk\DeveloperTools\XF\Entity\AddOn as ExtendedAddOnEntity;

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
        $ds = \XF::$DS;
        $buildUploadRoot = $addOn->getBuildDirectory();

        /** @var ExtendedAddOnEntity $addOnEntity */
        $addOnEntity = $this->addOn->getInstalledAddOn();

        $licenseAdded = false;
        $readmeAdded = false;

        if ($addOnEntity)
        {
            $developerOptions = $addOnEntity->DeveloperOptions;
            if (!empty($developerOptions['license']))
            {
                FileUtil::writeFile($buildUploadRoot . $ds . 'LICENSE.md', $developerOptions['license'], false);
                $licenseAdded = true;
            }

            if (!empty($developerOptions['readme']))
            {
                FileUtil::writeFile($buildUploadRoot . $ds . 'README.md', $developerOptions['readme'], false);
                $readmeAdded = true;
            }
        }

        $addOnRoot = $this->addOnRoot;
        $copyMarkdownFile = function ($possibleFileName) use($ds, $addOnRoot, $buildUploadRoot)
        {
            foreach ((array) $possibleFileName AS $fileName)
            {
                $filePath = $addOnRoot . $ds . $fileName;
                if (file_exists($filePath) && is_readable($filePath))
                {
                    FileUtil::copyFile($filePath, $buildUploadRoot . $ds . $filePath);
                }
            }
        };

        if (!$licenseAdded)
        {
            $copyMarkdownFile(['LICENSE', 'LICENSE.md']);
        }

        if (!$readmeAdded)
        {
            $copyMarkdownFile(['README', 'README.md']);
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

    /**
     * @param $fileName
     *
     * @return bool
     */
    protected function isExcludedFileName($fileName)
    {
        if (\in_array($fileName, ['git.json', 'dev.json']))
        {
            return true;
        }

        return parent::isExcludedFileName($fileName);
    }
}