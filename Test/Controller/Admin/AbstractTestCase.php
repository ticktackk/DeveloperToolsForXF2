<?php

namespace TickTackk\DeveloperTools\Test\Controller\Admin;

use TickTackk\DeveloperTools\Test\Controller\BaseTestController;

/**
 * Class AbstractTestCase
 *
 * @package TickTackk\DeveloperTools\Test\Controller\Admin
 */
abstract class AbstractTestCase extends BaseTestController
{
    protected static function appType(): string
    {
        return self::APP_TYPE_ADMIN;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->logout();
    }
}