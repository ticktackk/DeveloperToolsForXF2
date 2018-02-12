<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class CodeEvent extends XFCP_CodeEvent
{
    use CommitTrait;

    /**
     * @param \XF\Entity\CodeEvent $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'event_id'
        ];
    }
}