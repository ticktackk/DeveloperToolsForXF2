<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use TickTackk\DeveloperTools\App;

/**
 * Class TemplateModification
 *
 * @package TickTackk\DeveloperTools\XF\Entity
 */
class TemplateModification extends XFCP_TemplateModification
{
    protected function _postSave()
    {
        parent::_postSave();

        App::$modificationId = $this->modification_id;
    }
}