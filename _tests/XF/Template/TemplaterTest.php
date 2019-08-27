<?php

namespace TickTackk\DeveloperTools\XF\Template;

use TickTackk\DeveloperTools\Test\BaseTestCase;

/**
 * Class TemplaterTest
 *
 * @package TickTackk\DeveloperTools\XF\Template
 */
class TemplaterTest extends BaseTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAddTemplateWatcher()
    {
        $this->app()->options()->developerTools_TemplaterWatchDisable = '0';

        $templater = $this->app()->templater();
        $oldWatchers = $this->getPropertyAsPublic($templater, 'watchers');

        $templater->addTemplateWatcher($this->app()->container()['designer.output']->getHandler('XF:Template'));

        $newWatchers = $this->getPropertyAsPublic($templater, 'watchers');

        $this->assertCount(\count($oldWatchers) + 1, $newWatchers);
    }
}