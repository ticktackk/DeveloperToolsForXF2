<?php

namespace TickTackk\DeveloperTools\XF\Repository\Exception;

use Throwable;

/**
 * Class CodeEventNotFoundException
 *
 * This exception will be thrown when attempting to generate code event doc block and the code event does not exist.
 *
 * @package TickTackk\DeveloperTools\XF\Repository\Exception
 */
class CodeEventNotFoundException extends \InvalidArgumentException
{
    /**
     * @var string
     */
    protected $codeEvent;

    /**
     * CodeEventNotFoundException constructor.
     *
     * @param string         $codeEvent Name of the code event which is not found.
     * @param int            $code      Exit code.
     * @param Throwable|null $previous  Previously thrown exception (if any)
     */
    public function __construct(string $codeEvent, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Code event not found.', $code, $previous);

        $this->codeEvent = $codeEvent;
    }

    /**
     * Returns the stored code event
     *
     * @return string
     */
    public function getCodeEvent() : string
    {
        return $this->codeEvent;
    }
}