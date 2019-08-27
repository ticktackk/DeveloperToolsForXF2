<?php

namespace TickTackk\DeveloperTools\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use XF\Mvc\Entity\Structure;

/**
 * Class EntityStructureHasGetter
 *
 * @package TickTackk\DeveloperTools\Test\Constraint
 */
class EntityStructureHasGetter extends Constraint
{
    /**
     * @var string
     */
    private $getterName;

    /**
     * EntityStructureHasGetter constructor.
     *
     * @param string $getterName
     */
    public function __construct(string $getterName)
    {
        $this->getterName = $getterName;
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

        return !empty($other->getters[$this->getGetterName()]);
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            'has getter "%s"',
            $this->getGetterName()
        );
    }

    /**
     * yo dawg i heard u liked getters so i put a getter to get the getter name
     *
     * @return string
     */
    public function getGetterName(): string
    {
        return $this->getterName;
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