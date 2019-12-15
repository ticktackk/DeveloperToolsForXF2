<?php

namespace TickTackk\DeveloperTools;

use XF\App as BaseApp;

/**
 * Class Listener
 *
 * @package TickTackk\DeveloperTools
 */
class Listener
{
    /**
     * @param BaseApp $app
     */
    public static function appSetup(BaseApp $app) : void
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