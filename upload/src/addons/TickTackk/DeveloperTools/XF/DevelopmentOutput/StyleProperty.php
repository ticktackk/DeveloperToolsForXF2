<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class StyleProperty extends XFCP_StyleProperty
{
    use CommitTrait;

    /**
     * @param \XF\Entity\StyleProperty $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'property_name',
            'group_name',
            'title' => ['getTitle', '\__phrase']
        ];
    }
}