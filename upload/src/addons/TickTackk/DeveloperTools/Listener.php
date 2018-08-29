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
     * @param Entity $entity
     */
    public static function XFEntityTemplateModification_entity_post_save(Entity $entity)
    {
        self::$modificationId = $entity->getEntityId();
    }

    /**
     * @param \XF\App $app
     */
    public static function appSetup(\XF\App $app)
    {
        $ds = DIRECTORY_SEPARATOR;
        \XF::$autoLoader
            ->addPsr4('Bit3\\GitPhp\\', \XF::getRootDirectory() . $ds . "src{$ds}addons{$ds}TickTackk{$ds}DeveloperTools{$ds}vendor{$ds}bit3{$ds}git-php{$ds}src", true);
    }
}