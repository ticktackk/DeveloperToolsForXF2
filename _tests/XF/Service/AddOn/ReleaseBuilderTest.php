<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use TickTackk\DeveloperTools\Test\Service\AbstractTestCase;
use XF\Service\AddOn\ReleaseBuilder as AddOnReleaseBuilderSvc;
use TickTackk\DeveloperTools\XF\Service\AddOn\ReleaseBuilder as ExtendedAddOnReleaseBuilderSvc;
use ReflectionException;

/**
 * Class ReleaseBuilderTest
 *
 * @package TickTackk\DeveloperTools\XF\Service\AddOn
 */
class ReleaseBuilderTest extends AbstractTestCase
{
    /**
     * @return AddOnReleaseBuilderSvc|ExtendedAddOnReleaseBuilderSvc
     */
    protected function getReleaseBuilder() : AddOnReleaseBuilderSvc
    {
        $addOn = $this->app()->addOnManager()->getById('TickTackk/DeveloperTools');
        return $this->app()->service('XF:AddOn\ReleaseBuilder', $addOn);
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    protected function getExcludedDirectories()
    {
        $releaseBuilder = $this->getReleaseBuilder();

        return $this->getMethodAsPublic(
            $releaseBuilder,
            'getExcludedDirectories'
        )->invoke($releaseBuilder);
    }

    /**
     * @throws ReflectionException
     */
    public function testExcludedDirectoriesHas_repoDirectory()
    {
        $this->assertContains('_repo', $this->getExcludedDirectories());
    }

    /**
     * @throws ReflectionException
     */
    public function testExcludedDirectoriesHas_testsDirectory()
    {
        $this->assertContains('_tests', $this->getExcludedDirectories());
    }

    /**
     * @param string $fileName
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function isExcludedFileName(string $fileName)
    {
        $releaseBuilder = $this->getReleaseBuilder();

        return $this->getMethodAsPublic(
            $releaseBuilder,
            'isExcludedFileName'
        )->invokeArgs($releaseBuilder, [$fileName]);
    }

    /**
     * @throws ReflectionException
     */
    public function testIsGitJsonIsExcludedFile()
    {
        $this->assertTrue($this->isExcludedFileName('git.json'));
    }

    /**
     * @throws ReflectionException
     */
    public function testIsDevJsonIsExcludedFile()
    {
        $this->assertTrue($this->isExcludedFileName('dev.json'));
    }
}