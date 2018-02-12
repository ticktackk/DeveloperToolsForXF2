<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class Permission extends XFCP_Permission
{
    use CommitTrait;

    /**
     * @param \XF\Entity\Permission $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'permission_title' => ['getTitle', '\__phrase'],
            'permission_id'
        ];
    }
}