<?php

namespace TickTackk\DeveloperTools\Test\Entity;

use TickTackk\DeveloperTools\Test\BaseTestCase;
use TickTackk\DeveloperTools\Test\Constraint\EntityHasGetterMethod;
use TickTackk\DeveloperTools\Test\Constraint\EntityStructureHasColumn;
use TickTackk\DeveloperTools\Test\Constraint\EntityStructureHasGetter;
use TickTackk\DeveloperTools\Test\Constraint\EntityStructureHasRelation;
use TickTackk\DeveloperTools\Test\TestXF;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class AbstractTestCase
 *
 * @package TickTackk\DeveloperTools\Test\Entity
 */
abstract class AbstractTestCase extends BaseTestCase
{
    /**
     * @return Entity
     */
    protected static function readOnlyEntity() : Entity
    {
        $entity = TestXF::em()->create(self::shortClassName('Entity'));
        $entity->setReadOnly(true);

        return $entity;
    }

    /**
     * @return Structure
     */
    protected static function structure() : Structure
    {
        return static::readOnlyEntity()->structure();
    }

    /**
     * @param string $columnName
     * @param string $message
     */
    public function assertEntityStructureHasColumn(string $columnName, string $message = '') : void
    {
        static::assertThat(static::structure(), new EntityStructureHasColumn($columnName), $message);
    }

    /**
     * @param string $relationName
     * @param string $message
     */
    public function assertEntityStructureHasRelation(string $relationName, string $message = '') : void
    {
        static::assertThat(static::structure(), new EntityStructureHasRelation($relationName), $message);
    }

    /**
     * @param string $getterName
     * @param string $message
     */
    public function assertEntityStructureHasGetter(string $getterName, string $message = '') : void
    {
        static::assertThat(static::structure(), new EntityStructureHasGetter($getterName), $message);
    }

    /**
     * @param string $getterMethodName
     * @param string $message
     */
    public function assertEntityHasGetterMethod(string $getterMethodName, string $message = '') : void
    {
        static::assertThat(static::readOnlyEntity(), new EntityHasGetterMethod($getterMethodName), $message);
    }
}