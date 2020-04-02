<?php

namespace TickTackk\DeveloperTools\Cli\Command\Exception;

use Throwable;
use XF\Phrase;

/**
 * Class InvalidAddOnQuestionFieldAnswerException
 *
 * @package TickTackk\DeveloperTools\Cli\Command\Exception
 */
class InvalidAddOnQuestionFieldAnswerException extends \InvalidArgumentException
{
    protected $field;

    /**
     * InvalidAddOnQuestionFieldAnswerException constructor.
     *
     * @param string $field
     * @param string|Phrase $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $field, $message, $code = 0, Throwable $previous = null)
    {
        $this->field = $field;

        parent::__construct($message, $code, $previous);
    }
}