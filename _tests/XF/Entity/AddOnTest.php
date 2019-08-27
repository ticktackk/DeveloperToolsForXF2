<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use TickTackk\DeveloperTools\Test\Entity\AbstractTestCase;

/**
 * Class AddOnTest
 *
 * @package TickTackk\DeveloperTools\XF\Entity
 */
class AddOnTest extends AbstractTestCase
{
    public function testDeveloperOptionsGetterExistsInStructure() : void
    {
        static::assertEntityStructureHasGetter('DeveloperOptions');
    }

    public function testDeveloperOptionsGetterMethodExistsInStructure() : void
    {
        static::assertEntityHasGetterMethod('getDeveloperOptions');
    }

    public function testGitConfigurationsGetterExistsInStructure() : void
    {
        static::assertEntityStructureHasGetter('GitConfigurations');
    }
}
