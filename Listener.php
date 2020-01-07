<?php

namespace TickTackk\DeveloperTools;

use TickTackk\DeveloperTools\XF\Template\Templater as ExtendedTemplater;
use XF\Http\Response;
use XF\Mvc\Dispatcher;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Renderer\AbstractRenderer;
use XF\Util\File as FileUtil;

/**
 * Class Listener
 * 
 * This class declares code event listeners for the add-on.
 * 
 * @package TickTackk\DeveloperTools
 */
class Listener
{
    /**
     * @param Dispatcher       $dispatcher Dispatcher object
     * @param string           $content    The rendered content.
     * @param AbstractReply    $reply      Reply object.
     * @param AbstractRenderer $renderer   Renderer object.
     * @param Response         $response   HTTP Response object.
     */
    public static function dispatcherPostRender(Dispatcher $dispatcher, string &$content, AbstractReply $reply, AbstractRenderer $renderer, Response $response) : void
    {
        /** @var ExtendedTemplater $templater */
        $templater = $renderer->getTemplater();
        $permissionErrors = $templater->getPermissionErrors();

        if (\count($permissionErrors))
        {
            $warningHtml = '<div class="blockMessage blockMessage--warning"><h2 style="margin: 0 0 .5em 0">Permission errors</h2><ul>';
            foreach ($permissionErrors AS $permissionError)
            {
                $warningHtml .= sprintf('<li>%s (%s:%d)</li>',
                    htmlspecialchars($permissionError['error']),
                    htmlspecialchars(FileUtil::stripRootPathPrefix($permissionError['file'])),
                    $permissionError['line']
                );
            }
            $warningHtml .= '</ul></div>';

            if (strpos($content, '<!--XF:EXTRA_OUTPUT-->') !== false)
            {
                $content = str_replace('<!--XF:EXTRA_OUTPUT-->', $warningHtml . '<!--XF:EXTRA_OUTPUT-->', $content);
            }
            else
            {
                $content = preg_replace('#<body[^>]*>#i', "\\0$warningHtml", $content);
            }
        }
    }
}