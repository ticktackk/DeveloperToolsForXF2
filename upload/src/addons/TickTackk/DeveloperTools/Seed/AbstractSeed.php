<?php

namespace TickTackk\DeveloperTools\Seed;

use XF\Mvc\Entity\Entity;
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
     * AbstractSeed constructor.
     *
     * @param \XF\App $app
     */
    public function __construct(\XF\App $app)
    {
        $this->app = $app;
    }

    /**
     * @return \Faker\Generator
     */
    public function faker() : \Faker\Generator
    {
        return \Faker\Factory::create();
    }

    /**
     * @return int
     */
    public function getLimit() : int
    {
        return $this->limit;
    }

    /**
     * @param array $errors
     *
     * @return Entity|array
     */
    abstract protected function seedInternal(array &$errors = null);

    /**
     * @return bool|Entity
     * @throws PrintableException
     */
    public function run()
    {
        $result = $this->seedInternal($errors);

        if (\is_array($errors) && \count($errors))
        {
            throw new PrintableException(implode("\n", $errors));
        }

        return $result;
    }

    /**
     * @param $class
     *
     * @return \XF\Service\AbstractService
     */
    public function service(string $class) : \XF\Service\AbstractService
    {
        return $this->app->service($class);
    }

    /**
     * @param $identifier
     *
     * @return \XF\Mvc\Entity\Repository
     */
    public function repository(string $identifier) : \XF\Mvc\Entity\Repository
    {
        return $this->app->repository($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return \XF\Mvc\Entity\Finder
     */
    public function finder(string $identifier) : \XF\Mvc\Entity\Finder
    {
        return $this->app->finder($identifier);
    }
}