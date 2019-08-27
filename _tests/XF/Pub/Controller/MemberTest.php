<?php

namespace TickTackk\DeveloperTools\XF\Pub\Controller;

use TickTackk\DeveloperTools\Test\Controller\Pub\AbstractTestCase;

/**
 * Class MemberTest
 *
 * @package TickTackk\DeveloperTools\XF\Pub\Controller
 */
class MemberTest extends AbstractTestCase
{
    /**
     * @return string
     */
    protected static function getRoute() : string
    {
        return 'members';
    }

    public function testHasBuildAction()
    {
        $this->assertControllerHasAction($this->controller(), 'view');
    }
}