<?php

namespace TickTackk\DeveloperTools\XF\Repository;

use XF\Util\File;
use XF\Util\Json;

/**
 * Class AddOn
 *
 * @package TickTackk\DeveloperTools\XF\Repository
 */
class AddOn extends XFCP_AddOn
{
    /**
     * @param \XF\Entity\AddOn $addOn
     * @param array            $input
     */
    public function exportDeveloperOptions(\XF\Entity\AddOn $addOn, array $input) : void
    {
        $this->writeConfigForDeveloperTools($addOn, 'dev.json', $input);
    }

    /**
     * @param \XF\Entity\AddOn $addOn
     * @param array            $input
     */
    public function exportGitConfiguration(\XF\Entity\AddOn $addOn, array $input) : void
    {
        $this->writeConfigForDeveloperTools($addOn, 'git.json', $input);
    }

    /**
     * @param \XF\Entity\AddOn $addOnEntity
     * @param string           $fileName
     * @param array            $input
     */
    protected function writeConfigForDeveloperTools(\XF\Entity\AddOn $addOnEntity, $fileName, array $input) : void
    {
        if (\XF::$versionId >= 2010000)
        {
            $addOn = new \XF\AddOn\AddOn($addOnEntity, \XF::app()->addOnManager());
        }
        else
        {
            $addOn = new \XF\AddOn\AddOn($addOnEntity);
        }
        $jsonPath = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . $fileName;

        File::writeFile($jsonPath, Json::jsonEncodePretty($input), false);
    }

    /**
     * @param \XF\Entity\AddOn $addOn
     *
     * @return array|mixed
     */
    public function getDeveloperOptions(\XF\Entity\AddOn $addOn) : array
    {
        return $this->readConfigForDeveloperTools($addOn, 'dev.json');
    }

    /**
     * @param \XF\Entity\AddOn $addOn
     *
     * @return array|mixed
     */
    public function getGitConfigurations(\XF\Entity\AddOn $addOn) : array
    {
        return $this->readConfigForDeveloperTools($addOn, 'git.json');
    }

    /**
     * @param \XF\Entity\AddOn $addOnEntity
     * @param string           $fileName
     *
     * @return array|mixed
     */
    protected function readConfigForDeveloperTools(\XF\Entity\AddOn $addOnEntity, $fileName) : array
    {
        if (\XF::$versionId >= 2010000)
        {
            $addOn = new \XF\AddOn\AddOn($addOnEntity, \XF::app()->addOnManager());
        }
        else
        {
            $addOn = new \XF\AddOn\AddOn($addOnEntity);
        }
        $jsonPath = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($jsonPath) || !is_readable($jsonPath))
        {
            return [];
        }

        return json_decode(file_get_contents($jsonPath), true);
    }
}