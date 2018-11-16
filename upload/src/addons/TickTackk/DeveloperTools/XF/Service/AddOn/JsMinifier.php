<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

/**
 * Extends \XF\Service\AddOn\JsMinifier
 */
class JsMinifier extends XFCP_JsMinifier
{
    /**
     * @return bool|null|string
     * @throws \ErrorException
     */
    public function minify()
    {
        $xfRoot = \XF::getSourceDirectory();
        $development = \XF::config('development');
        // from https://developers.google.com/closure/compiler/docs/gettingstarted_app
        $closureJar = empty($development['closureJar']) ? $xfRoot . DIRECTORY_SEPARATOR . 'closure-compiler-v20180402.jar' : $development['closureJar'];
        if ($closureJar && file_exists($closureJar))
        {
            passthru("java -jar {$closureJar} --js {$this->jsPath} --js_output_file {$this->minPath}", $returnVar);
            if ($returnVar !== 0)
            {
                throw new \ErrorException('Unable to minify ' . $this->jsPath);
            }

            return true;
        }

        return parent::minify();
    }
}