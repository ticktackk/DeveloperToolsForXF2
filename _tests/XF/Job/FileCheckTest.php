<?php

namespace TickTackk\DeveloperTools\XF\Job;

use TickTackk\DeveloperTools\Test\Constraint\JobResultIs;
use TickTackk\DeveloperTools\Test\Job\AbstractTestCase;
use XF\Entity\FileCheck as FileCheckEntity;

/**
 * Class FileCheckTest
 *
 * @package TickTackk\DeveloperTools\XF\Job
 */
class FileCheckTest extends AbstractTestCase
{
    /**
     * @var FileCheckEntity
     */
    protected $fileHashCheck;

    /**
     * @throws \XF\PrintableException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileHashCheck = $this->app()->em()->create('XF:FileCheck');
        $this->fileHashCheck->save();
    }

    public function testJobReturnsCompleteResultWhenHasCheckDisabled() : void
    {
        $this->app()->options()->developerTools_HashCheckDisable = '1';

        $jobResult = $this->runJob([
            'check_id' => $this->fileHashCheck->check_id
        ]);

        $this->assertJobResultIs(
            $jobResult,
            static::className(null, false),
            JobResultIs::JOB_RESULT_COMPLETE
        );
    }
}