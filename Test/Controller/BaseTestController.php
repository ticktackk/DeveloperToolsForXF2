<?php

namespace TickTackk\DeveloperTools\Test\Controller;

use TickTackk\DeveloperTools\Test\BaseAppTrait;
use TickTackk\DeveloperTools\Test\BaseTestCase;
use TickTackk\DeveloperTools\Test\Constraint\ControllerHasAction;
use TickTackk\DeveloperTools\Test\Constraint\ControllerReplyIs;
use TickTackk\DeveloperTools\Test\TestXF;
use TickTackk\DeveloperTools\XF\Mvc\Dispatcher as MvcDispatcherExtended;
use XF\ControllerPlugin\AbstractPlugin;
use XF\ControllerPlugin\Login as LoginControllerPlugin;
use XF\Entity\User as UserEntity;
use XF\Mvc\Controller as MvcController;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Error as ErrorReply;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Mvc\Reply\Message as MessageReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Mvc\Reply\Reroute as RerouteReply;
use XF\Mvc\Reply\View as ViewReply;
use ReflectionException;

/**
 * Class BaseTestController
 *
 * @package TickTackk\DeveloperTools\Test\Controller
 */
abstract class BaseTestController extends BaseTestCase
{
    abstract protected static function getRoute() : string;

    /**
     * @param string $pluginName
     *
     * @return AbstractPlugin
     */
    protected function plugin(string $pluginName) : AbstractPlugin
    {
        return $this->controller()->plugin($pluginName);
    }

    /**
     * @param UserEntity $user
     * @param bool $applyValidCsrf
     * @param bool $applyValidToken
     *
     * @throws ReflectionException
     */
    protected function loginAs(UserEntity $user, bool $applyValidCsrf = true, bool $applyValidToken = true) : void
    {
        /** @var LoginControllerPlugin $loginPlugin */
        $loginPlugin = $this->plugin('XF:Login');
        $loginPlugin->completeLogin($user, true);

        /** @var TestXF|BaseAppTrait $app */
        $app = TestXF::app();
        $app->start(true);

        if ($applyValidCsrf)
        {
            $app->applyValidCsrf();
        }

        if ($applyValidToken)
        {
            $app->applyValidToken();
        }
    }

    protected function logout() : void
    {
        /** @var LoginControllerPlugin $loginPlugin */
        $loginPlugin = $this->plugin('XF:Login');
        $loginPlugin->logoutVisitor();

        TestXF::app()->start(true);
    }

    /**
     * @param string|null $class
     *
     * @return MvcController
     */
    protected function controller(string $class = null) : MvcController
    {
        return $this->app()->controller(
            static::className($class),
            $this->app()->request()
        );
    }

    /**
     * @param string|null $action
     *
     * @return string
     */
    protected function getRoutePath(string $action = null) : string
    {
        $routePath = ltrim($this->getRoute());

        if ($action)
        {
            $routePath .= "/{$action}";
        }

        return $routePath . '/';
    }

    /**
     * @param string $action
     *
     * @return AbstractReply
     */
    protected function reply(string $action = '') : AbstractReply
    {
        $routePath = $this->getRoutePath($action);

        /** @var MvcDispatcherExtended $dispatcher */
        $dispatcher = $this->app()->dispatcher();
        $dispatcher->run($routePath);

        return $dispatcher->getReply();
    }

    /**
     * @return string
     */
    protected function errorReplyClass() : string
    {
        return ErrorReply::class;
    }

    /**
     * @return string
     */
    protected function exceptionReplyClass() : string
    {
        return ExceptionReply::class;
    }

    /**
     * @return string
     */
    protected function getMessageReplyClass() : string
    {
        return MessageReply::class;
    }

    /**
     * @return string
     */
    protected function redirectReplyClass() : string
    {
        return RedirectReply::class;
    }

    /**
     * @return string
     */
    protected function rerouteReplyClass() : string
    {
        return RerouteReply::class;
    }

    /**
     * @return string
     */
    protected function viewReplyClass() : string
    {
        return ViewReply::class;
    }

    /**
     * @param MvcController $controller
     * @param string $action
     * @param string $message
     */
    public static function assertControllerHasAction(MvcController $controller, string $action, string $message = ''): void
    {
        static::assertThat($controller, new ControllerHasAction($action), $message);
    }

    /**
     * @param AbstractReply $reply
     * @param string $expectedReplyClass
     * @param string $message
     */
    public static function assertReplyIs(AbstractReply $reply, string $expectedReplyClass, string $message = '') : void
    {
        static::assertThat($reply, new ControllerReplyIs(self::className(null, false), $expectedReplyClass), $message);
    }
}