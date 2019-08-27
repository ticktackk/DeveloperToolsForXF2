<?php

namespace TickTackk\DeveloperTools\Test\Repository;

use TickTackk\DeveloperTools\Test\BaseTestCase;
use XF\Mvc\Entity\Repository;

/**
 * Class AbstractTestCase
 *
 * @package TickTackk\DeveloperTools\Test\Repository
 */
abstract class AbstractTestCase extends BaseTestCase
{
    /**
     * @param string|null $identifier
     *
     * @return Repository
     */
    protected function repository(string $identifier = null) : Repository
    {
        $identifier = $identifier ?: static::shortClassName('Repository');
        return $this->app()->repository($identifier);
    }
}