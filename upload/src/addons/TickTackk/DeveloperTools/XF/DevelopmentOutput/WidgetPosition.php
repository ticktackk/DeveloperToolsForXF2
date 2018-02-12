<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class WidgetPosition extends XFCP_WidgetPosition
{
    use CommitTrait;

    /**
     * @param \XF\Entity\WidgetPosition $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'title' => ['getTitle', '\__phrase'],
            'position_id' => $entity->position_id
        ];
    }
}