<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class AdminPermission extends XFCP_AdminPermission
{
    use CommitTrait;

    /**
     * @param \XF\Entity\AdminPermission $entity
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