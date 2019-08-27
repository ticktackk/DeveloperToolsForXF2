<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use TickTackk\DeveloperTools\Test\Controller\Admin\AbstractTestCase;

/**
 * Class TemplateTest
 *
 * @package TickTackk\DeveloperTools\XF\Admin\Controller
 */
class TemplateTest extends AbstractTestCase
{
    /**
     * @return string
     */
    protected static function getRoute() : string
    {
        return 'templates';
    }

    public function testHasViewModificationsAction()
    {
        $this->assertControllerHasAction($this->controller(), 'viewModifications');
    }
}