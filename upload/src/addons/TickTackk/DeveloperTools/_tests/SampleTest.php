<?php

namespace TickTackk\DeveloperTools\Tests;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class SampleTest
 */
class SampleTest extends \PHPUnit\Framework\TestCase
{
    public function testTrueAssertsTrue()
    {
        $this->assertEquals(true, true);
    }

    public function testTrueAssertsTruez()
    {
        $this->assertEquals(true, true);
    }
}