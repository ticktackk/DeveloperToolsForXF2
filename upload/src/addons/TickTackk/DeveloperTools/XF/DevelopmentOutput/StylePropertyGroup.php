<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class StylePropertyGroup extends XFCP_StylePropertyGroup
{
    use CommitTrait;

    /**
     * @param \XF\Entity\StylePropertyGroup $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'group_name',
            'title' => ['getTitle', '\__phrase']
        ];
    }
}