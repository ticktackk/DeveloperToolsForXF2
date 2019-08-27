<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use TickTackk\DeveloperTools\Test\Controller\Admin\AbstractTestCase;
use TickTackk\DeveloperTools\Test\TestXF;
use XF\Entity\User as UserEntity;
use XF\Repository\User as UserRepo;

/**
 * Class AddOnTest
 *
 * @package TickTackk\DeveloperTools\XF\Admin\Controller
 */
class AddOnTest extends AbstractTestCase
{
    /**
     * @return string
     */
    protected static function getRoute() : string
    {
        return 'add-ons';
    }

    public function testHasBuildAction() : void
    {
        $this->assertControllerHasAction($this->controller(), 'build');
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildActionReplyAsAdmin() : void
    {
        /** @var UserRepo $userRepo */
        $userRepo = $this->app()->repository('XF:User');

        /** @var UserEntity $admin */
        $admin = $userRepo->findValidUsers()
            ->where('is_admin', true)
            ->fetchOne();

        $this->loginAs($admin);

        $reply = $this->reply('build');

        $this->assertReplyIs($reply, $this->viewReplyClass());
    }
}