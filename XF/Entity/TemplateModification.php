<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use TickTackk\DeveloperTools\App;
use XF\Mvc\Entity\Structure;
use XF\Phrase;

/**
 * Class TemplateModification
 *
 * @package TickTackk\DeveloperTools\XF\Entity
 */
class TemplateModification extends XFCP_TemplateModification
{
    /**
     * @return Phrase
     */
    public function getTypePhrase() : Phrase
    {
        return \XF::phrase($this->type);
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['TypePhrase'] = true;

        return $structure;
    }
}