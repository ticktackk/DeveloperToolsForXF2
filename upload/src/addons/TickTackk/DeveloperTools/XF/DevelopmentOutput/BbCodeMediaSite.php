<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class BbCodeMediaSite extends XFCP_BbCodeMediaSite
{
    use CommitTrait;

    /**
     * @param \XF\Entity\BbCodeMediaSite $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'media_site_title' => ['getTitle', '\__phrase'],
            'media_site_id'
        ];
    }
}