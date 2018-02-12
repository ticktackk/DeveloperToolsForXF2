<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class Option extends XFCP_Option
{
    use CommitTrait;

    /**
     * @param \XF\Entity\Option $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'option_title' => ['getTitle', '\__phrase'],
            'option_id'
        ];
    }
}