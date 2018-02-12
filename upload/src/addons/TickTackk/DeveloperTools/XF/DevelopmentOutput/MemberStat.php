<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class MemberStat extends XFCP_MemberStat
{
    use CommitTrait;

    /**
     * @param \XF\Entity\MemberStat $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'member_stat_key',
            'member_stat_title' => ['getTitle', '\__phrase']
        ];
    }
}