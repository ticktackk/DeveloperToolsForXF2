<?php

namespace TickTackk\DeveloperTools\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class EntityHasGetterMethod extends Constraint
{
    /**
     * @var string
     */
    private $getterMethodName;

    /**
     * EntityHasGetterMethod constructor.
     *
     * @param string $getterMethodName
     */
    public function __construct(string $getterMethodName)
    {
        $this->getterMethodName = $getterMethodName;
    }

    /**
     * @param Entity $other
     *
     * @return bool
     */
    protected function matches($other): bool
    {
        if (!$other instanceof Entity)
        {
            return false;
        }

        return method_exists($other, $this->getGetterMethodName());
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            'has getter method "%s"',
            $this->getGetterMethodName()
        );
    }

    /**
     * @return string
     */
    public function getGetterMethodName(): string
    {
        return $this->getterMethodName;
    }

    /**
     * @param Entity $other
     *
     * @return string
     */
    protected function failureDescription($other): string
    {
        if (!$other instanceof Entity)
        {
            if (is_object($other))
            {
                return get_class($other) . ' is not instance \XF\Mvc\Entity\Entity';
            }

            return gettype($other) . ' provided, but expected object instance of \XF\Mvc\Entity\Entity';
        }

        return $other->shortName . ' ' . $this->toString();
    }
}