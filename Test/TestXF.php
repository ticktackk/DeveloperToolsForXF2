<?php

namespace TickTackk\DeveloperTools\Test;

use XF;
use XF\App as XFApp;
use XF\Container as XFContainer;

/**
 * Class TestXF
 *
 * @package TickTackk\DeveloperTools\Test
 */
class TestXF extends XF
{
    /**
     * @param string $appClass
     */
    public static function runApp($appClass)
    {
        /** @var XFApp $app */
        $app = new $appClass(new XFContainer());
        self::$app = $app;
        $app->setup([]);
        $response = $app->run();

        $extraOutput = ob_get_contents();
        if (strlen($extraOutput))
        {
            $body = $response->body();
            if (is_string($body))
            {
                if ($response->contentType() == 'text/html')
                {
                    if (strpos($body, '<!--XF:EXTRA_OUTPUT-->') !== false)
                    {
                        $body = str_replace('<!--XF:EXTRA_OUTPUT-->', $extraOutput . '<!--XF:EXTRA_OUTPUT-->', $body);
                    }
                    else
                    {
                        $body = preg_replace('#<body[^>]*>#i', "\\0$extraOutput", $body);
                    }
                    $response->body($body);
                }
                else
                {
                    $response->body($extraOutput . $body);
                }
            }
        }

        if (static::$debugMode)
        {
            $container = $app->container();

            if ($container->isCached('db'))
            {
                $queryCount = $app->db()->getQueryCount();
            }
            else
            {
                $queryCount = null;
            }

            $debug = [
                'time' => round(microtime(true) - $app->container('time.granular'), 4),
                'queries' => $queryCount,
                'memory' => round(memory_get_peak_usage() / 1024 / 1024, 2)
            ];

            $response->header('X-XF-Debug-Stats', json_encode($debug));
        }
    }
}