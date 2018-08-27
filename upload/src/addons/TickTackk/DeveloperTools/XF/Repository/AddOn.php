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
    public function exportDeveloperOptions(\XF\Entity\AddOn $addOn, array $input)
    {
        $ds = DIRECTORY_SEPARATOR;

        $addOnIdForPath = $addOn->addon_id;
        if (strpos($addOnIdForPath, '/') !== false)
        {
            $addOnIdForPath = str_replace('/', $ds, $addOnIdForPath);
        }
        $jsonPath = \XF::getAddOnDirectory() . $ds . $addOnIdForPath . $ds . 'dev.json';

        File::writeFile($jsonPath, Json::jsonEncodePretty($input), false);
    }

    /**
     * @param \XF\Entity\AddOn $addOn
     *
     * @return array|mixed
     */
    public function getDeveloperOptions(\XF\Entity\AddOn $addOn)
    {
        $ds = DIRECTORY_SEPARATOR;

        $addOnIdForPath = $addOn->addon_id;
        if (strpos($addOnIdForPath, '/') !== false)
        {
            $addOnIdForPath = str_replace('/', $ds, $addOnIdForPath);
        }
        $jsonPath = \XF::getAddOnDirectory() . $ds . $addOnIdForPath . $ds . 'dev.json';

        if (!file_exists($jsonPath) || !is_readable($jsonPath))
        {
            return [];
        }

        return json_decode(file_get_contents($jsonPath), true);
    }
}