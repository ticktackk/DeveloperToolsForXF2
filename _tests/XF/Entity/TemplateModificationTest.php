<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use TickTackk\DeveloperTools\Test\Entity\AbstractTestCase;

/**
 * Class TemplateModificationTest
 *
 * @package TickTackk\DeveloperTools\XF\Entity
 */
class TemplateModificationTest extends AbstractTestCase
{
    public function testTypePhraseGetterExistsInStructure() : void
    {
        static::assertEntityStructureHasGetter('TypePhrase');
    }
}
