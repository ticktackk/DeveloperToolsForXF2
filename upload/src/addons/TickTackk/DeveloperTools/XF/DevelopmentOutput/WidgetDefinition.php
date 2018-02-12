<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class WidgetDefinition extends XFCP_WidgetDefinition
{
    use CommitTrait;

    /**
     * @param \XF\Entity\WidgetDefinition $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'title' => ['getTitle', '\__phrase'],
            'definition_id'
        ];
    }
}