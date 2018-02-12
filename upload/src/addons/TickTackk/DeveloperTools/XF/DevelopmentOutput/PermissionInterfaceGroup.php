<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class PermissionInterfaceGroup extends XFCP_PermissionInterfaceGroup
{
    use CommitTrait;

    /**
     * @param \XF\Entity\PermissionInterfaceGroup $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'interface_title' => ['getTitle', '\__phrase'],
            'interface_group_id'
        ];
    }
}