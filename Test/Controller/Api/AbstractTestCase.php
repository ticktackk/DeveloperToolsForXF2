<?php

namespace TickTackk\DeveloperTools\Test\Controller\Api;

use TickTackk\DeveloperTools\Test\Controller\BaseTestController;

/**
 * Class AbstractTestCase
 *
 * @package TickTackk\DeveloperTools\Test\Controller\Api
 */
abstract class AbstractTestCase extends BaseTestController
{
    protected static function appType(): string
    {
        return self::APP_TYPE_API;
    }
}