<?php

namespace TickTackk\DeveloperTools;

use XF\App as BaseApp;

/**
 * Copyright (c) Simon Hampel
 * Based on code used by Composer, which is Copyright (c) Nils Adermann, Jordi Boggiano
 */
class Composer
{
    /**
     * @param BaseApp $app
     * @param bool    $prepend
     */
    public static function autoloadNamespaces(BaseApp $app, $prepend = false): void
    {
        $namespaces = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_namespaces.php';

        if (!\file_exists($namespaces))
        {
            $app->error()->logError('Missing vendor autoload files at %s', $namespaces);
        }
        else
        {
            /** @noinspection PhpIncludeInspection */
            $map = require $namespaces;

            foreach ($map as $namespace => $path)
            {
                \XF::$autoLoader->add($namespace, $path, $prepend);
            }
        }
    }

    /**
     * @param BaseApp $app
     * @param bool    $prepend
     */
    public static function autoloadPsr4(BaseApp $app, $prepend = false): void
    {
        $psr4 = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_psr4.php';

        if (!\file_exists($psr4))
        {
            $app->error()->logError('Missing vendor autoload files at %s', $psr4);
        }
        else
        {
            /** @noinspection PhpIncludeInspection */
            $map = require $psr4;

            foreach ($map as $namespace => $path)
            {
                \XF::$autoLoader->addPsr4($namespace, $path, $prepend);
            }
        }
    }

    /**
     * @param BaseApp $app
     */
    public static function autoloadClassmap(BaseApp $app): void
    {
        $classmap = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_classmap.php';

        if (!\file_exists($classmap))
        {
            $app->error()->logError('Missing vendor autoload files at %s', $classmap);
        }
        else
        {
            /** @noinspection PhpIncludeInspection */
            $map = require $classmap;

            if ($map)
            {
                \XF::$autoLoader->addClassMap($map);
            }
        }
    }

    /**
     * @param BaseApp $app
     */
    public static function autoloadFiles(BaseApp $app): void
    {
        $files = __DIR__ . DIRECTORY_SEPARATOR . '_vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_files.php';

        // note that autoload_files.php is only generated if there is actually a 'files' directive somewhere in the dependency chain
        if (\file_exists($files))
        {
            /** @noinspection PhpIncludeInspection */
            $includeFiles = require $files;

            foreach ($includeFiles as $fileIdentifier => $file)
            {
                if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier]))
                {
                    /** @noinspection PhpIncludeInspection */
                    require $file;

                    $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
                }
            }
        }
    }
}