<?php

namespace TickTackk\DeveloperTools;

use XF\Mvc\Entity\Entity;

class Listener
{
    public static function XFEntityAddOn_entity_structure(/** @noinspection PhpUnusedParameterInspection */
        \XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure)
    {
        $structure->columns['license'] = ['type' => Entity::STR, 'default' => ''];
        $structure->columns['gitignore'] = ['type' => Entity::STR, 'default' => ''];
        $structure->columns['readme_md'] = ['type' => Entity::STR, 'default' => ''];
    }

    public static $modificationId;

    public static function XFEntityTemplateModification_entity_post_save(\XF\Mvc\Entity\Entity $entity)
    {
        \TickTackk\DeveloperTools\Listener::$modificationId = $entity->getEntityId();
    }
}