<?php

namespace TickTackk\DeveloperTools\Util;

use TickTackk\DeveloperTools\Test\BaseTestCase;

/**
 * Class AddOnTest
 *
 * @package TickTackk\DeveloperTools\Util
 */
class AddOnTest extends BaseTestCase
{
    protected const CLASS_FORMAT = 'Formatter';

    protected const FULL_CLASS = 'TickTackk\\DeveloperTools\\Formatter\\ClassName';

    protected const SHORT_CLASS = 'TickTackk\\DeveloperTools:ClassName';

    public function testClassToStringIfNotAlreadyShortClass()
    {
        $this->assertSame(
            AddOn::classToString(static::SHORT_CLASS, static::CLASS_FORMAT), static::SHORT_CLASS
        );
    }

    public function testClassToStringIfAlreadyShortClass()
    {
        $this->assertSame(
            AddOn::classToString(static::FULL_CLASS, static::CLASS_FORMAT), static::SHORT_CLASS
        );
    }
}