<?php

namespace TickTackk\DeveloperTools\Service\Listener\Exception;

use Throwable;

/**
 * Class InvalidAddOnIdProvidedException
 *
 * @package TickTackk\DeveloperTools\Service\Listener\Exception
 */
class InvalidAddOnIdProvidedException extends \InvalidArgumentException
{
    /**
     * @var string
     */
    protected $addOnId;

    /**
     * InvalidAddOnIdProvidedException constructor.
     *
     * @param string $addOnId
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $addOnId, $code = 0, ?Throwable $previous = null)
    {
        $this->addOnId = $addOnId;

        parent::__construct('Invalid add-on ID provided.', $code, $previous);
    }

    /**
     * @return string
     */
    public function getAddOnId() : string
    {
        return $this->addOnId;
    }
}