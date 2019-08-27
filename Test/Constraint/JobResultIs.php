<?php

namespace TickTackk\DeveloperTools\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use XF\Job\JobResult;

/**
 * Class JobResultIs
 *
 * @package TickTackk\DeveloperTools\Test\Constraint
 */
class JobResultIs extends Constraint
{
    public const JOB_RESULT_COMPLETE = 0;

    public const JOB_RESULT_INCOMPLETE = 1;

    /**
     * @var string
     */
    private $jobClass;

    /**
     * @var int
     */
    private $jobResult;

    /**
     * JobResultIs constructor.
     *
     * @param string $jobClass
     * @param int $jobResult
     */
    public function __construct(string $jobClass, int $jobResult)
    {
        $this->jobClass = $jobClass;
        $this->jobResult = $jobResult;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        switch ($this->getJobResult())
        {
            case self::JOB_RESULT_COMPLETE:
                return 'job result is completed';

            case self::JOB_RESULT_INCOMPLETE:
                return 'job result is not completed';
        }

        return 'unknown job result provided';
    }

    /**
     * @param mixed $other
     *
     * @return bool
     */
    protected function matches($other): bool
    {
        if (!$other instanceof JobResult)
        {
            return false;
        }

        switch ($this->getJobResult())
        {
            case self::JOB_RESULT_COMPLETE:
                return $other->completed === true;

            case self::JOB_RESULT_INCOMPLETE:
                return $other->completed === false;
        }

        return false;
    }

    protected function failureDescription($other): string
    {
        if (!$other instanceof JobResult)
        {
            if (is_object($other))
            {
                return get_class($other) . ' is not instance \XF\Job\Result';
            }

            return gettype($other) . ' provided, but expected object instance of \XF\job\Result';
        }

        return $this->getJobClass() . ' ' . $this->toString();
    }

    /**
     * @return string
     */
    public function getJobClass(): string
    {
        return $this->jobClass;
    }

    /**
     * @return int
     */
    public function getJobResult(): int
    {
        return $this->jobResult;
    }
}