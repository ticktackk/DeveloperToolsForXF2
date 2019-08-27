<?php

namespace TickTackk\DeveloperTools\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use XF\Mvc\Entity\Structure;

/**
 * Class EntityStructureHasRelation
 *
 * @package TickTackk\DeveloperTools\Test\Constraint
 */
class EntityStructureHasRelation extends Constraint
{
    /**
     * @var string
     */
    private $relationName;

    /**
     * EntityStructureHasRelation constructor.
     *
     * @param string $relationName
     */
    public function __construct(string $relationName)
    {
        $this->relationName = $relationName;
    }

    /**
     * @param mixed $other
     *
     * @return bool
     */
    protected function matches($other): bool
    {
        if (!$other instanceof Structure)
        {
            return false;
        }

        return !empty($other->relations[$this->getRelationName()]);
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            'has relation "%s"',
            $this->getRelationName()
        );
    }

    /**
     * @return string
     */
    public function getRelationName(): string
    {
        return $this->relationName;
    }

    protected function failureDescription($other): string
    {
        if (!$other instanceof Structure)
        {
            if (is_object($other))
            {
                return get_class($other) . ' is not instance \XF\Mvc\Entity\Structure';
            }

            return gettype($other) . ' provided, but expected object instance of \XF\Mvc\Entity\Structure';
        }

        return $other->shortName . ' ' . $this->toString();
    }
}