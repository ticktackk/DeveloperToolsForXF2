<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class AdvertisingPosition extends XFCP_AdvertisingPosition
{
    use CommitTrait;

    /**
     * @param \XF\Entity\AdvertisingPosition $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'position_title' => ['getTitle', '\__phrase'],
            'position_id'
        ];
    }
}