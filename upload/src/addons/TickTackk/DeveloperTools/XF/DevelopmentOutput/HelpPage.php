<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class HelpPage extends XFCP_HelpPage
{
    use CommitTrait;

    /**
     * @param \XF\Entity\HelpPage $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'page_id',
            'page_name',
            'page_title' => ['getTitle', '\__phrase']
        ];
    }
}