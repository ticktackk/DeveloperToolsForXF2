<?php

namespace TickTackk\DeveloperTools\Test\Job;

use TickTackk\DeveloperTools\Test\BaseTestCase;
use TickTackk\DeveloperTools\Test\Constraint\JobResultIs;
use XF\Job\AbstractJob;
use XF\Job\JobResult;

abstract class AbstractTestCase extends BaseTestCase
{
    /**
     * @var int
     */
    protected $maxRunTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maxRunTime = $this->app()->config('jobMaxRunTime');
    }

    /**
     * @return string
     */
    protected function getJobId()
    {
        return 'job-' . crc32(static::shortClassName('Job'));
    }

    /**
     * @param array $params
     *
     * @return AbstractJob
     */
    public function job(array $params = []) : AbstractJob
    {
        return $this->app()->job(
            static::shortClassName('Job'),
            $this->getJobId(),
            $params
        );
    }

    /**
     * @param array $params
     *
     * @return JobResult
     */
    public function runJob(array $params = []) : JobResult
    {
        return $this->job($params)->run($this->maxRunTime);
    }

    /**
     * Asserts that a job result is completed or otherwise.
     *
     * @param JobResult $jobResult
     * @param string $jobClass
     * @param int $possibleResult
     * @param string $message
     */
    public static function assertJobResultIs(JobResult $jobResult, string $jobClass, int $possibleResult, string $message = ''): void
    {
        static::assertThat($jobResult, new JobResultIs($jobClass, $possibleResult), $message);
    }
}