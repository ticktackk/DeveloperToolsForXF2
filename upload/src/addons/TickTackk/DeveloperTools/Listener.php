<?php

namespace TickTackk\DeveloperTools;

/**
 * Class Listener
 *
 * @package TickTackk\DeveloperTools
 */
class Listener
{
    /**
     * @param \XF\App $app
     */
    public static function appSetup(\XF\App $app) : void
    {
        if (\XF::$versionId < 2010010)
        {
            Composer::autoloadNamespaces($app);
            Composer::autoloadPsr4($app);
            Composer::autoloadClassmap($app);
            Composer::autoloadFiles($app);
        }
    }
}