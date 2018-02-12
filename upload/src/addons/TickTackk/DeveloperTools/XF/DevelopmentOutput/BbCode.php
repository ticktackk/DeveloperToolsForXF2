<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class BbCode extends XFCP_BbCode
{
    use CommitTrait;

    /**
     * @param \XF\Entity\BbCode $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'bb_code_title' => ['getTitle', '\__phrase'],
            'bb_code_id'
        ];
    }
}