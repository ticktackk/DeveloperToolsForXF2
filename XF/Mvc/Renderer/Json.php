<?php

namespace TickTackk\DeveloperTools\XF\Mvc\Renderer;

use TickTackk\DeveloperTools\XF\Template\Templater as ExtendedTemplater;
use XF\Util\File;

use function count;

/**
 * Class Json
 * 
 * Extends \XF\Mvc\Renderer\Json
 *
 * @package TickTackk\DeveloperTools\XF\Mvc\Renderer
 */
class Json extends XFCP_Json
{
    /**
     * @param string $html The rendered content.
     *
     * @return array The output structure which
     */
    public function getHtmlOutputStructure($html)
    {
        $output = parent::getHtmlOutputStructure($html);

        /** @var ExtendedTemplater $templater */
        $templater = $this->getTemplater();
        $permissionErrors = $templater->getPermissionErrors();

        if (count($permissionErrors))
        {
            $output['permissionErrors'] = true;

            $permissionErrorDetails = [];
            foreach ($permissionErrors AS $permissionError)
            {
                $permissionErrorDetails[] = sprintf('%s (%s:%d)',
                    $permissionError['error'],
                    File::stripRootPathPrefix($permissionError['file']),
                    $permissionError['line']
                );
            }
            $output['permissionErrorDetails'] = $permissionErrorDetails;
        }

        return $output;
    }
}