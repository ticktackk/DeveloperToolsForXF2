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

    /**
     * @param \XF\Cli\App $app
     */
    public static function appCliSetup(\XF\Cli\App $app) : void
    {
        $app->container()->factory('seed', function($class, array $params, Container $c) use ($app)
        {
            $class = \XF::stringToClass($class, '\%s\Seeds\%s');
            $class = $app->extendClass($class);

            array_unshift($params, $app);

            return $c->createObject($class, $params);
        }, false);
    }
}