<?php

namespace TickTackk\DeveloperTools\XF\Template;

use XF\Template\WatcherInterface;

/**
 * Extends \XF\Template\Templater
 */
class Templater extends XFCP_Templater
{
    public function addTemplateWatcher(WatcherInterface $watcher)
    {
        if (\XF::options()->developerTools_TemplaterWatchDisable)
        {
            return;
        }
        parent::addTemplateWatcher($watcher);
    }
}
