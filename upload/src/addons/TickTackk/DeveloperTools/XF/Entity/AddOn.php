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
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['DeveloperOptions'] = true;

        return $structure;
    }
}