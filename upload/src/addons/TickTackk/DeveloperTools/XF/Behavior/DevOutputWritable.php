<?php

namespace TickTackk\DeveloperTools\XF\Behavior;

use XF\Mvc\Entity\Entity;

class DevOutputWritable extends XFCP_DevOutputWritable
{
    public function postSave()
    {
        $this->cloneEntity($this->entity);
        parent::postSave();
    }

    public function preDelete()
    {
        $this->cloneEntity($this->entity);
        parent::preDelete();
    }

    protected function cloneEntity(Entity $entity)
    {
        \XF::app()->developmentOutput()->cloneEntity($entity);
    }
}