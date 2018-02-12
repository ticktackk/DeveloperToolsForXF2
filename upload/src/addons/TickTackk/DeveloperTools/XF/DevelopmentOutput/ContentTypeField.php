<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class ContentTypeField extends XFCP_ContentTypeField
{
    use CommitTrait;

    /**
     * @param \XF\Entity\ContentTypeField $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'content_type',
            'field_name',
            'field_value'
        ];
    }
}