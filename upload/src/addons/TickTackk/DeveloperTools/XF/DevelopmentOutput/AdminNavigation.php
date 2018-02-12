<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class AdminNavigation extends XFCP_AdminNavigation
{
    use CommitTrait;

    /**
     * @param \XF\Entity\AdminNavigation $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'navigation_title' => ['getTitle', '\__phrase'],
            'navigation_id' => $entity->getEntityId()
        ];
    }
}