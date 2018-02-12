<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class TemplateModification extends XFCP_TemplateModification
{
    use CommitTrait;

    /**
     * @param \XF\Entity\TemplateModification $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'type',
            'template',
            'modification_key',
            'description'
        ];
    }
}