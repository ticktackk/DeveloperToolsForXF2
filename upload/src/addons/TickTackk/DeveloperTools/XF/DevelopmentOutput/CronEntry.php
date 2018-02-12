<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class CronEntry extends XFCP_CronEntry
{
    use CommitTrait;

    /**
     * @param \XF\Entity\CronEntry $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'entry_id',
            'cron_class',
            'cron_method',
            'entry_title' => ['getTitle', '\__phrase']
        ];
    }
}