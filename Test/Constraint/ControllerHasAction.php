<?php

namespace TickTackk\DeveloperTools\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use XF\Mvc\Controller as MvcController;

class ControllerHasAction extends Constraint
{
    /**
     * @var string
     */
    private $actionName;

    /**
     * ControllerHasAction constructor.
     * @param string $actionName
     */
    public function __construct(string $actionName)
    {
        $this->actionName = $actionName;
    }

    /**
     * @param mixed $other
     *
     * @return bool
     */
    protected function matches($other): bool
    {
        if (!$other instanceof MvcController)
        {
            return false;
        }

        return method_exists($other, $this->actionMethodName());
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            'has action "%s"',
            ucfirst($this->actionName())
        );
    }

    /**
     * @return string
     */
    protected function actionName() : string
    {
        return $this->actionName;
    }

    /**
     * @return string
     */
    protected function actionMethodName() : string
    {
        return 'action' . ucfirst($this->actionName());
    }

    protected function failureDescription($other): string
    {
        if (!$other instanceof MvcController)
        {
            if (is_object($other))
            {
                return get_class($other) . ' is not instance \XF\Mvc\Controller';
            }

            return gettype($other) . ' provided, but expected object instance of \XF\Mvc\Controller';
        }

        return get_class($other) . ' ' . $this->toString();
    }
}