<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class ClassExtension extends XFCP_ClassExtension
{
    use CommitTrait;

    /**
     * @param \XF\Entity\ClassExtension $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $entity)
    {
        return [
            'from_class',
            'to_class'
        ];
    }
}