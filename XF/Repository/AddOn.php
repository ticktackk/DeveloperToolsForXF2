<?php

namespace TickTackk\DeveloperTools\XF\Repository;

use XF\Util\File;
use XF\Util\Json;
use XF\Entity\AddOn as AddOnEntity;

/**
 * Class AddOn
 *
 * @package TickTackk\DeveloperTools\XF\Repository
 */
class AddOn extends XFCP_AddOn
{
    /**
     * @param AddOnEntity $addOn
     * @param array $input
     */
    public function exportDeveloperOptions(AddOnEntity $addOn, array $input) : void
    {
        $this->writeConfigForDeveloperTools($addOn, 'dev.json', $input);
    }

    /**
     * @param AddOnEntity $addOn
     * @param array $input
     */
    public function exportGitConfiguration(AddOnEntity $addOn, array $input) : void
    {
        $this->writeConfigForDeveloperTools($addOn, 'git.json', $input);
    }

    /**
     * @param AddOnEntity $addOnEntity
     * @param string $fileName
     * @param array $input
     */
    protected function writeConfigForDeveloperTools(AddOnEntity $addOnEntity, string $fileName, array $input) : void
    {
        if (\XF::$versionId >= 2010000)
        {
            $addOn = new \XF\AddOn\AddOn($addOnEntity, $this->app()->addOnManager());
        }
        else
        {
            /** @noinspection PhpParamsInspection */
            $addOn = new \XF\AddOn\AddOn($addOnEntity);
        }

        $jsonPath = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . $fileName;

        File::writeFile($jsonPath, Json::jsonEncodePretty($input), false);
    }

    /**
     * @param AddOnEntity $addOn
     *
     * @return array
     */
    public function getDeveloperOptions(AddOnEntity $addOn) : array
    {
        return $this->readConfigForDeveloperTools($addOn, 'dev.json');
    }

    /**
     * @param AddOnEntity $addOn
     *
     * @return array
     */
    public function getGitConfigurations(AddOnEntity $addOn) : array
    {
        return $this->readConfigForDeveloperTools($addOn, 'git.json');
    }

    /**
     * @param AddOnEntity $addOnEntity
     * @param string $fileName
     * @return array
     */
    protected function readConfigForDeveloperTools(AddOnEntity $addOnEntity, string $fileName) : array
    {
        if (\XF::$versionId >= 2010000)
        {
            $addOn = new \XF\AddOn\AddOn($addOnEntity, $this->app()->addOnManager());
        }
        else
        {
            /** @noinspection PhpParamsInspection */
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