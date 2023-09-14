<?php

namespace TickTackk\DeveloperTools\XF\Template;

use XF\Template\WatcherInterface;

/**
 * Class Templater
 *
 * Extends \XF\Template\Templater
 *
 * @package TickTackk\DeveloperTools\XF\Template
 */
class Templater extends XFCP_Templater
{
    use TemplaterTrait;

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     *
     * @param WatcherInterface $watcher
     */
    public function addTemplateWatcher(WatcherInterface $watcher)
    {
        if (\XF::options()->developerTools_TemplaterWatchDisable)
        {
            return;
        }

        parent::addTemplateWatcher($watcher);
    }
}