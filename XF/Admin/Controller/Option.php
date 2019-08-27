<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Entity\Option as OptionEntity;
use XF\Entity\OptionGroup as OptionGroupEntity;
use XF\Entity\OptionGroupRelation as OptionGroupRelationEntity;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Reply\View;

/**
 * Class Option
 *
 * @package TickTackk\DeveloperTools\XF\Admin\Controller
 */
class Option extends XFCP_Option
{
    /**
     * @param OptionEntity $option
     * @param array        $baseRelations
     *
     * @return View
     */
    public function optionAddEdit(\XF\Entity\Option $option, $baseRelations = [])
    {
        $reply = parent::optionAddEdit($option, $baseRelations);

        if ($reply instanceof View && !$option->exists() && $this->request()->exists('group_id'))
        {
            /** @var ArrayCollection|OptionGroupEntity[] $groups */
            $groups = $reply->getParam('groups');

            /** @var OptionGroupEntity $group */
            $group = $groups[$this->filter('group_id', 'str')] ?? false;
            if ($group)
            {
                $reply->setParam('group', $group);
            }
        }

        return $reply;
    }
}