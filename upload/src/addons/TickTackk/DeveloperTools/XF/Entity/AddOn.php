<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Class AddOn
 *
 * @package TickTackk\DeveloperTools\XF\Entity
 *
 * GETTERS
 * @property array DeveloperOptions
 * @property array GitConfigurations
 */
class AddOn extends XFCP_AddOn
{
    /**
     * @return array|mixed
     */
    public function getDeveloperOptions()
    {
        /** @var \TickTackk\DeveloperTools\XF\Repository\AddOn $addOnRepo */
        $addOnRepo = $this->repository('XF:AddOn');
        return $addOnRepo->getDeveloperOptions($this);
    }

    /**
     * @return array|mixed
     */
    public function getGitConfigurations()
    {
        /** @var \TickTackk\DeveloperTools\XF\Repository\AddOn $addOnRepo */
        $addOnRepo = $this->repository('XF:AddOn');
        return $addOnRepo->getGitConfigurations($this);
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['DeveloperOptions'] = true;
        $structure->getters['GitConfigurations'] = true;

        return $structure;
    }
}