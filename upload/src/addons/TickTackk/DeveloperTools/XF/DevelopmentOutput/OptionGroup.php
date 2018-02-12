<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class OptionGroup extends XFCP_OptionGroup
{
    use CommitTrait;

    /**
     * @param \XF\Entity\OptionGroup $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'option_group_title' => ['getTitle', '\__phrase'],
            'group_id'
        ];
    }
}