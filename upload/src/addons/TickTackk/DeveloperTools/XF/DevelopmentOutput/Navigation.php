<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class Navigation extends XFCP_Navigation
{
    use CommitTrait;

    /**
     * @param \XF\Entity\Navigation $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'navigation_title' => ['getTitle', '\__phrase'],
            'navigation_id'
        ];
    }
}