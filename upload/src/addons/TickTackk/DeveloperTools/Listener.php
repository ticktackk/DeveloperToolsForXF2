<?php

namespace TickTackk\DeveloperTools;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

/**
 * Class Listener
 *
 * @package TickTackk\DeveloperTools
 */
class Listener
{
    public static $modificationId;

    /**
     * @param Manager   $em
     * @param Structure $structure
     */
    public static function XFEntityAddOn_entity_structure(/** @noinspection PhpUnusedParameterInspection */
        Manager $em, Structure &$structure)
    {
        $structure->columns['devTools_license'] = ['type' => Entity::STR, 'default' => ''];
        $structure->columns['devTools_gitignore'] = ['type' => Entity::STR, 'default' => ''];
        $structure->columns['devTools_readme_md'] = ['type' => Entity::STR, 'default' => ''];
        $structure->columns['devTools_parse_additional_files'] = ['type' => Entity::BOOL, 'default' => false];
    }

    /**
     * @param Entity $entity
     */
    public static function XFEntityTemplateModification_entity_post_save(Entity $entity)
    {
        self::$modificationId = $entity->getEntityId();
    }
}