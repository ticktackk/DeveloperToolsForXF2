<?php

namespace TickTackk\DeveloperTools\XF\Mvc;

use XF\Http;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\RouteMatch;
use XF\App as XFApp;
use XF\PrintableException;

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

    public function dispatchLoop(RouteMatch $match)
    {
        $reply = parent::dispatchLoop($match);

        $this->reply = $reply;

        return $reply;
    }
}