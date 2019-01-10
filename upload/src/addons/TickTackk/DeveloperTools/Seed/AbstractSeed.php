<?php

namespace TickTackk\DeveloperTools\Seed;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Finder;
use XF\PrintableException;

/**
 * Class AbstractSeed
 *
 * @package TickTackk\DeveloperTools\Seed
 */
abstract class AbstractSeed
{
    /**
     * @var \XF\App
     */
    protected $app;

    /**
     * @var int
     */
    protected $limit = 100;

    /**
     * @var \XF\Entity\User
     */
    protected $runAs;

    /**
     * AbstractSeed constructor.
     *
     * @param \XF\App $app
     */
    public function __construct(\XF\App $app)
    {
        $this->app = $app;

        /** @var \XF\Entity\User $randomUser */
        $randomUser = $this->randomEntity('XF:User');
        $this->setRunAsUser($randomUser ?: \XF::visitor());
    }

    /**
     * @return \Faker\Generator
     */
    public function faker(): \Faker\Generator
    {
        return \Faker\Factory::create();
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param \XF\Entity\User $user
     */
    public function setRunAsUser(\XF\Entity\User $user)
    {
        $this->runAs = $user;
    }

    /**
     * @param array $errors
     *
     * @return Entity|array
     */
    abstract protected function seedInternal(array &$errors = null);

    /**
     * @return mixed
     * @throws PrintableException
     * @throws \Exception
     */
    public function run()
    {
        $errors = [];
        $result = \XF::asVisitor($this->runAs, function ()
        {
            return $this->seedInternal($errors);
        });

        if (\is_array($errors) && \count($errors))
        {
            throw new PrintableException(implode("\n", $errors));
        }

        return $result;
    }

    /**
     * @param string      $identifier
     * @param string|null $orderBy
     * @param int         $limit
     *
     * @return array|int|string|null
     */
    protected function randomEntity(string $identifier, string $orderBy = null, int $limit = 1)
    {
        $items = $this->finder($identifier)
            ->order($orderBy ?: Finder::ORDER_RANDOM)
            ->limit($limit)
            ->fetch();

        if (!$items->count())
        {
            return null;
        }

        return $limit === 1 ? $items->first() : $items;
    }

    /**
     * @param $class
     *
     * @return \XF\Service\AbstractService
     */
    protected function service(string $class): \XF\Service\AbstractService
    {
        return call_user_func_array([$this->app, 'service'], func_get_args());
    }

    /**
     * @param $identifier
     *
     * @return \XF\Mvc\Entity\Repository
     */
    protected function repository(string $identifier): \XF\Mvc\Entity\Repository
    {
        return $this->app->repository($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return \XF\Mvc\Entity\Finder
     */
    protected function finder(string $identifier): \XF\Mvc\Entity\Finder
    {
        return $this->app->finder($identifier);
    }
}