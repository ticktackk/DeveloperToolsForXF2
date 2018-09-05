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
    protected $limit;

    /** @var Entity|bool */
    protected $lastResult;

    /**
     * AbstractSeed constructor.
     *
     * @param \XF\App $app
     * @param int     $limit
     */
    public function __construct(\XF\App $app, $limit = 10)
    {
        $this->app = $app;
        $this->limit = $limit;
    }

    /**
     * @return \Faker\Generator
     */
    public function faker() : \Faker\Generator
    {
        return \Faker\Factory::create();
    }

    /**
     * @param array $errors
     *
     * @return Entity|array
     */
    abstract protected function _seed(array &$errors = null);

    /**
     * @throws PrintableException
     */
    public function run() : void
    {
        if (!$this->limit)
        {
            throw new \InvalidArgumentException('Limit has been set to invalid value.');
        }

        for ($i = 0; $i <= $this->limit; $i++)
        {
            $result = $this->_seed($errors);
            if ($errors)
            {
                throw new PrintableException(implode("\n", $errors));
            }
            $this->lastResult = $result;
        }
    }

    /**
     * @param $class
     *
     * @return \XF\Service\AbstractService
     */
    public function service($class) : \XF\Service\AbstractService
    {
        return $this->app->service($class);
    }

    /**
     * @param $identifier
     *
     * @return \XF\Mvc\Entity\Repository
     */
    public function repository($identifier) : \XF\Mvc\Entity\Repository
    {
        return $this->app->repository($identifier);
    }
}