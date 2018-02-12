<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class Route extends XFCP_Route
{
    use CommitTrait;

    /**
     * @param \XF\Entity\Route $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'route_prefix',
            'route_type'
        ];
    }
}