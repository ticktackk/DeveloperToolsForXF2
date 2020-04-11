<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use TickTackk\DeveloperTools\XF\Service\AddOn\Exception\ClosureCompilerNotFoundException;

/**
 * Extends \XF\Service\AddOn\JsMinifier
 */
class JsMinifier extends XFCP_JsMinifier
{
    /**
     * @return bool|string|null
     *
     * @throws ClosureCompilerNotFoundException
     * @throws \ErrorException
     */
    public function minify()
    {
        $xfRoot = \XF::getSourceDirectory();
        $development = $this->app->config('development');
        $jsPath = $this->jsPath;

        // from https://developers.google.com/closure/compiler/docs/gettingstarted_app
        $closureJarPath = $development['closureJar'] ?? null;
        if (!$closureJarPath)
        {
            return parent::minify();
        }

        if (!\file_exists($closureJarPath))
        {
            throw new ClosureCompilerNotFoundException();
        }

        \passthru("java -jar {$closureJarPath} --js {$jsPath} --js_output_file {$this->minPath}", $exitCode);
        if ($exitCode !== 0)
        {
            throw new \ErrorException('Unable to minify ' . $jsPath);
        }

        return true;
    }
}