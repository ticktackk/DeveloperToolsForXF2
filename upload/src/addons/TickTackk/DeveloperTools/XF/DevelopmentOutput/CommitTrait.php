<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use XF\DevelopmentOutput;
use XF\Mvc\Entity\Entity;

trait CommitTrait
{
    protected $entityClone = [];

    protected $delayJob = false;

    protected $delayJobBy = 15;

    public function commitRepo($jobId, $repoDir, $actionType, $isUpdate)
    {
        if (!method_exists($this, 'getOutputCommitData'))
        {
            return;
        }

        $entityClone = $this->entityClone;

        if (empty($this->entityClone))
        {
            return;
        }

        $runTime = \XF::$time;

        if ($this->delayJob)
        {
            $runTime = $runTime + $this->delayJobBy;
        }

        \XF::app()->jobManager()->enqueueLater($jobId, $runTime, 'TickTackk\DeveloperTools:Commit', [
            'actionType' => $actionType,
            'typeDir' => $this->getTypeDir(),
            'repoDir' => $repoDir,
            'entityClone' => $entityClone,
            'delayedJob' => $this->delayJob,
            'isUpdate' => $isUpdate
        ], false);
    }

    public function cloneEntity(Entity $entity, array $outputDataKeys)
    {
        $this->entityClone = $this->pullEntityKeysForCommit($entity, $outputDataKeys);
        if (!empty($this->entityClone))
        {
            $this->entityClone['shortName'] = $entity->structure()->shortName;
        }
    }

    protected function pullEntityKeysForCommit(Entity $entity, array $keys)
    {
        $json = [];

        foreach ($keys as $key => $value)
        {
            if (is_array($value) && !$entity instanceof \XF\Entity\Phrase)
            {
                if ($value[1] != '\__phrase')
                {
                    if (!is_object($value[0]))
                    {
                        return []; // i have no idea this wasn't working for 3 days straight i need srs help pls
                    }

                    $actualValue = $value[0]->$value[1];
                }
                else
                {
                    $this->delayJob = true;
                    $json[$key] = call_user_func_array(
                        array($entity, $value[0]),
                        []
                    );
                    $actualValue = $json[$key] . '\__phrase';
                }
                $json[$key] = $actualValue;
            }
            else
            {
                if ($value != 'shortName')
                {
                    $json[$value] = $entity->isValidColumn($value) ? $entity->getValue($value) : $entity->get($value);
                }
            }
        }

        return $json;
    }
}