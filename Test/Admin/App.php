<?php

namespace TickTackk\DeveloperTools\Test\Admin;

use TickTackk\DeveloperTools\Test\BaseAppTrait;
use TickTackk\DeveloperTools\Test\PhpHelperTrait;
use XF\Admin\App as AdminApp;

/**
 * Class App
 *
 * @package TickTackk\DeveloperTools\Test\Admin
 */
class App extends AdminApp
{
    use PhpHelperTrait, BaseAppTrait;
}