<?php

namespace TickTackk\DeveloperTools\Test\Pub;

use TickTackk\DeveloperTools\Test\BaseAppTrait;
use TickTackk\DeveloperTools\Test\PhpHelperTrait;
use XF\Pub\App as PubApp;

/**
 * Class App
 *
 * @package TickTackk\DeveloperTools\Test\Pub
 */
class App extends PubApp
{
    use PhpHelperTrait, BaseAppTrait;
}