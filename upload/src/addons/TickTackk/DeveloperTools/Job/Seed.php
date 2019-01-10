<?php

namespace TickTackk\DeveloperTools\Job;

use XF\Job\AbstractJob;

/**
 * Class Seed
 *
 * @package TickTackk\DeveloperTools\Job
 */
class Seed extends AbstractJob
{
    /**
     * @var array
     */
    protected $defaultData = [
        'seeds' => [],
        'inProgressSeed' => null,
        'seedStats' => [],
        'total' => null
    ];

    /**
     * @param $maxRunTime
     *
     * @return \XF\Job\JobResult
     * @throws \XF\PrintableException
     */
    public function run($maxRunTime) : \XF\Job\JobResult
    {
        if (!\count($this->data['seeds']))
        {
            return $this->complete();
        }

        if ($this->data['inProgressSeed'] === null)
        {
            $this->data['inProgressSeed'] = reset($this->data['seeds']);
        }

        foreach ($this->data['seeds'] AS $seed)
        {
            if ($seed === $this->data['inProgressSeed'])
            {
                $seeder = $this->seed($seed);

                if (empty($this->data['seedStats'][$seed]))
                {
                    $this->data['seedStats'][$seed] = [
                        'done' => 0,
                        'limit' => $seeder->getLimit()
                    ];
                }

                if ($this->data['seedStats'][$seed]['done'] === $this->data['seedStats'][$seed]['limit'])
                {
                    $this->data['inProgressSeed'] = $seed;
                    break;
                }

                if ($success = $seeder->run())
                {
                    $this->data['seedStats'][$seed]['done']++;
                }
            }
        }

        $lastSeed = end($this->data['seeds']);
        if ($this->data['inProgressSeed'] === $lastSeed && $this->data['seedStats'][$lastSeed]['done'] === $this->data['seedStats'][$lastSeed]['limit'])
        {
            return $this->complete();
        }

        return $this->resume();
    }

    /**
     * @return string
     */
    public function getStatusMessage() : string
    {
        $inProgressSeed = $this->data['inProgressSeed'];
        $done = 0;
        $limit = 0;
        if (isset($this->data['seedStats'][$inProgressSeed]))
        {
            $done = $this->data['seedStats'][$inProgressSeed]['done'];
            $limit = $this->data['seedStats'][$inProgressSeed]['limit'];
        }

        return sprintf(
            '%s %s (%d/%d)...',
            'Seeding',
            $inProgressSeed,
            $done + 1,
            $limit
        );
    }

    /**
     * @return bool
     */
    public function canTriggerByChoice() : bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canCancel() : bool
    {
        return true;
    }

    /**
     * @param $class
     *
     * @return \TickTackk\DeveloperTools\Seed\AbstractSeed
     */
    protected function seed($class) : \TickTackk\DeveloperTools\Seed\AbstractSeed
    {
        $app = \XF::app();

        $arguments = \func_get_args();
        unset($arguments[0]);

        return $app->create('seed', $class, $arguments);
    }
}