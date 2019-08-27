<?php

namespace TickTackk\DeveloperTools;

/**
 * Copyright (c) Simon Hampel
 * Based on code used by Composer, which is Copyright (c) Nils Adermann, Jordi Boggiano
 */
class Composer
{
    /**
     * @param \XF\App $app
     * @param bool    $prepend
     */
    public static function autoloadNamespaces(\XF\App $app, $prepend = false): void
    {
        $namespaces = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_namespaces.php';

        if (!file_exists($namespaces))
        {
            $app->error()->logError('Missing vendor autoload files at %s', $namespaces);
        }
        else
        {
            $map = require $namespaces;

            foreach ($map as $namespace => $path)
            {
                \XF::$autoLoader->add($namespace, $path, $prepend);
            }
        }
    }

    /**
     * @param \XF\App $app
     * @param bool    $prepend
     */
    public static function autoloadPsr4(\XF\App $app, $prepend = false): void
    {
        $psr4 = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_psr4.php';

        if (!file_exists($psr4))
        {
            $app->error()->logError('Missing vendor autoload files at %s', $psr4);
        }
        else
        {
            $map = require $psr4;

            foreach ($map as $namespace => $path)
            {
                \XF::$autoLoader->addPsr4($namespace, $path, $prepend);
            }
        }
    }

    /**
     * @param \XF\App $app
     */
    public static function autoloadClassmap(\XF\App $app): void
    {
        $classmap = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_classmap.php';

        if (!file_exists($classmap))
        {
            $app->error()->logError('Missing vendor autoload files at %s', $classmap);
        }
        else
        {
            $map = require $classmap;

            if ($map)
            {
                \XF::$autoLoader->addClassMap($map);
            }
        }
    }

    /**
     * @param \XF\App $app
     */
    public static function autoloadFiles(\XF\App $app): void
    {
        $files = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_files.php';

        // note that autoload_files.php is only generated if there is actually a 'files' directive somewhere in the dependency chain
        if (file_exists($files))
        {
            $includeFiles = require $files;

            foreach ($includeFiles as $fileIdentifier => $file)
            {
                if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier]))
                {
                    require $file;

                    $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
                }
            }
        }
    }
}