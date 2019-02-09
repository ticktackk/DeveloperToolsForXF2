<?php

namespace TickTackk\DeveloperTools;

use XF\Mvc\Entity\Entity;
use XF\Container;

/**
 * Class Listener
 *
 * @package TickTackk\DeveloperTools
 */
class Listener
{
    public static $modificationId;

    /**
     * @param Entity $entity
     */
    public static function XFEntityTemplateModification_entity_post_save(Entity $entity)
    {
        self::$modificationId = $entity->getEntityId();
    }
}