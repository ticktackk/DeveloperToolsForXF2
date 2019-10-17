<?php

namespace TickTackk\DeveloperTools\XF\Mvc;

use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\RouteMatch;

/**
 * Class Dispatcher
 *
 * @package TickTackk\DeveloperTools\XF\Mvc
 */
class Dispatcher extends XFCP_Dispatcher
{
    /**
     * @var AbstractReply
     */
    protected $reply;

    /**
     * @return AbstractReply
     */
    public function getReply() :? AbstractReply
    {
        return $this->reply;
    }

    /**
     * @param RouteMatch $match
     *
     * @return null|AbstractReply
     */
    public function dispatchLoop(RouteMatch $match)
    {
        $reply = parent::dispatchLoop($match);

        $this->reply = $reply;

        return $reply;
    }
}