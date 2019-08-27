<?php

namespace TickTackk\DeveloperTools\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use XF\Mvc\Entity\Structure;

class EntityStructureHasColumn extends Constraint
{
    /**
     * @var string
     */
    private $columnName;

    /**
     * EntityStructureHasColumn constructor.
     *
     * @param string $columnName
     */
    public function __construct(string $columnName)
    {
        $this->columnName = $columnName;
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

        return !empty($other->columns[$this->getColumnName()]);
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            'has column "%s"',
            $this->getColumnName()
        );
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
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