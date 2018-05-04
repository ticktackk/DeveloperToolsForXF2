<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use XF\Util\File;

/**
 * Class TemplateModification
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

        $addOnId = $this->addOn->getAddOnId();
        $addOn = $this->em()->findOne('XF:AddOn', ['addon_id' => $addOnId]);

        if (!empty($addOn->devTools_license))
        {
            File::writeFile($buildUploadRoot . $ds . 'LICENSE.md', $addOn->devTools_license, false);
        }

        if (!empty($addOn->devTools_readme_md))
        {
            File::writeFile($buildUploadRoot . $ds . 'README.md', $addOn->devTools_readme_md, false);
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