<?php

namespace TickTackk\DeveloperTools\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use XF\Mvc\Reply\AbstractReply;

class ControllerReplyIs extends Constraint
{
    /**
     * @var string
     */
    private $controllerClass;
    
    /**
     * @var string
     */
    private $expectedReplyClass;

    /**
     * ControllerReplyIs constructor.
     * 
     * @param string $controllerClass
     * @param string $expectedReplyClass
     */
    public function __construct(string $controllerClass, string $expectedReplyClass)
    {
        $this->controllerClass = $controllerClass;
        $this->expectedReplyClass = $expectedReplyClass;
    }

    /**
     * @param mixed $other
     *
     * @return bool
     */
    protected function matches($other): bool
    {
        if (!$other instanceof AbstractReply)
        {
            return false;
        }

        return is_a($other, $this->getExpectedReplyClass());
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            'reply is %s',
            strtolower($this->getExpectedReplyClass(true))
        );
    }

    /**
     * @param bool $simple
     * @return string
     */
    public function getExpectedReplyClass(bool $simple = false): string
    {
        $expectedReplyClass = $this->expectedReplyClass;

        if ($simple)
        {
            $expectedReplyClassArr = explode('\\', $expectedReplyClass);
            $expectedReplyClass = end($expectedReplyClassArr);
        }

        return $expectedReplyClass;
    }

    /**
     * @return string
     */
    public function getControllerClass(): string
    {
        return $this->controllerClass;
    }

    protected function failureDescription($other): string
    {
        if (!$other instanceof AbstractReply)
        {
            if (is_object($other))
            {
                return get_class($other) . ' is not instance \XF\Mvc\Reply\AbstractReply';
            }

            return gettype($other) . ' provided, but expected object instance of \XF\Mvc\Reply\AbstractReply';
        }

        return $this->getControllerClass() . '::' . $other->getAction() . ' ' . $this->toString();
    }
}