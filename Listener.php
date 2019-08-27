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

    public static function requestUrlMatchesAdmin() : bool
    {
        $baseRequest = new \XF\Http\Request(new \XF\InputFilterer());
        $adminFile = 'admin.php';
        $scriptName = $baseRequest->getServer('SCRIPT_NAME', '');

        if (utf8_strlen($scriptName) <= utf8_strlen($adminFile))
        {
            return false;
        }

        return utf8_substr($scriptName, (utf8_strlen($scriptName) - (utf8_strlen($adminFile) + 1))) === $adminFile;
    }
}