<?php

namespace TickTackk\DeveloperTools\Service\CodeEvent;

use XF\App as BaseApp;
use XF\Service\AbstractService;

/**
 * Class SignatureGenerator
 *
 * @package TickTackk\DeveloperTools\Service\CodeEvent
 */
class SignatureGenerator extends AbstractService
{
    /**
     * @var array
     */
    protected $parsedDescription;

    /**
     * DocBlockGenerator constructor.
     *
     * @param BaseApp $app
     * @param array $parsedDescription
     */
    public function __construct(BaseApp $app, array $parsedDescription)
    {
        parent::__construct($app);

        $this->parsedDescription = $parsedDescription;
    }

    /**
     * @return array
     */
    public function getParsedDescription() : array
    {
        return $this->parsedDescription;
    }

    /**
     * @return string
     */
    public function generate() : string
    {
        $parsedDescription = $this->getParsedDescription();
        $signature = '';

        foreach ($parsedDescription['arguments'] AS $argumentData)
        {
            ['hint' => $hint, 'name' => $argument, 'passedByRef' => $passedByRef] = $argumentData;

            if ($hint && $hint !== 'mixed')
            {
                $signature .= "{$hint} ";
            }

            if ($passedByRef)
            {
                $signature .= '&';
            }

            $signature .= "{$argument}, ";
        }

        return \rtrim(\trim($signature), ',');
    }
}