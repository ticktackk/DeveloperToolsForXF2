<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use XF\Util\File;

class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    protected function prepareFilesToCopy()
    {
        parent::prepareFilesToCopy();
        $this->prepareFilesToCopyForRepo();
    }

    protected function prepareFilesToCopyForRepo()
    {
        $addOn = $this->addOn;
        $ds = DIRECTORY_SEPARATOR;
        $buildUploadRoot = $addOn->getBuildDirectory();

        $addOnId = $this->addOn->getAddOnId();
        $addOn = $this->em()->findOne('XF:AddOn', ['addon_id' => $addOnId]);

        if (!empty($addOn->license))
        {
            $licenseContent = <<< LICENSE
{$addOn->license}
LICENSE;
            File::writeFile($buildUploadRoot . $ds . 'LICENSE.md', $licenseContent, false);
        }

        if (!empty($addOn->readme_md))
        {
            $readMeMarkdownContent = <<< README_MD
{$addOn->readme_md}
README_MD;
            File::writeFile($buildUploadRoot . $ds . 'README.md', $readMeMarkdownContent, false);
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

    /**
     * @return bool
     *
     * @throws \ErrorException
     * @throws \XF\PrintableException
     */
    public function build()
    {
        $build = parent::build();

        if ($build)
        {
            File::deleteDirectory($this->addOnRoot . DIRECTORY_SEPARATOR . '_data');
        }

        return $build;
    }
}