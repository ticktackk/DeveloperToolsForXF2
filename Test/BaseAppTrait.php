<?php

namespace TickTackk\DeveloperTools\Test;

use ReflectionException;
use TickTackk\DeveloperTools\XF\Extension;
use XF\Container;
use XF\Db\Exception as DbException;
use XF\Http\Request;
use XF\Mvc\Entity\Manager as EntityManager;

/**
 * Trait BaseAppTrait
 *
 * @package TickTackk\DeveloperTools\Test
 */
trait BaseAppTrait
{
    use PhpHelperTrait;

    /**
     * @param string|null $csrf
     * @throws ReflectionException
     */
    public function applyValidCsrf(string $csrf = null) : void
    {
        /** @var Request $request */
        $request = $this->request();

        $cookie = static::getPropertyAsPublic($request, 'cookie');
        $cookie[$request->getCookiePrefix() . 'csrf'] = $csrf ?: $this->updateCsrfCookie;

        static::setInaccessibleProperty($request, 'cookie', $cookie);
    }

    /**
     * @param string|null $token
     */
    public function applyValidToken(string $token = null) : void
    {
        /** @var Request $request */
        $request = $this->request();
        $request->set('_xfToken', $token ?: $this->container('csrf.token'));
    }

    public function initializeExtra()
    {
        $container = $this->container;

        if (!$container['extension'] instanceof Extension)
        {
            $container['extension'] = function(Container $c)
            {
                $config = $c['config'];
                if (!$config['enableListeners'])
                {
                    // disable
                    return new Extension();
                }

                try
                {
                    $listeners = $c['extension.listeners'];
                    $classExtensions = $c['extension.classExtensions'];
                }
                catch (DbException $e)
                {
                    $listeners = [];
                    $classExtensions = [];
                }

                return new Extension($listeners, $classExtensions);
            };

            $container['em'] = function (Container $c)
            {
                return new EntityManager($c['db'], $c['em.valueFormatter'], $c['extension']);
            };
        }

        /** @noinspection PhpUndefinedClassInspection */
        parent::initializeExtra();
    }
}