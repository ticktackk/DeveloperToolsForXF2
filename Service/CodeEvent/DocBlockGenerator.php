<?php

namespace TickTackk\DeveloperTools\Service\CodeEvent;

use XF\Service\AbstractService;
use XF\App as BaseApp;

use function array_key_exists, count, strlen;

/**
 * Class DocBlockGenerator
 *
 * @package TickTackk\DeveloperTools\Service\CodeEvent
 */
class DocBlockGenerator extends AbstractService
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

    protected function getPadLengthForArgument(array $arguments, string $key) : int
    {
        $padLength = 0;

        foreach ($arguments AS $argument)
        {
            if (array_key_exists($key, $argument))
            {
                $possiblePadLength = strlen($argument[$key]);
                if ($possiblePadLength > $padLength)
                {
                    $padLength = $possiblePadLength;
                }
            }
        }

        return $padLength;
    }

    /**
     * @return string
     */
    public function generate() : string
    {
        $parsedDescription = $this->getParsedDescription();

        $wrappedDescription = wordwrap($parsedDescription['description'], 95, PHP_EOL . '     * ');

        $docBlock = <<<TEXT

    /**
TEXT;
        if ($wrappedDescription)
        {
            $docBlock .= PHP_EOL . <<<TEXT
     * {$wrappedDescription}
     *
TEXT;
        }

        if ($parsedDescription['eventHint'])
        {
            $docBlock .= PHP_EOL . <<<TEXT
     * Event hint: {$parsedDescription['eventHint']}
     * 
TEXT;
        }

        if (count($parsedDescription['arguments']))
        {
            $docBlock .= PHP_EOL;

            $hintPadLength = $this->getPadLengthForArgument($parsedDescription['arguments'], 'hint');
            $namePadLength = $this->getPadLengthForArgument($parsedDescription['arguments'], 'name');
            $descriptionPadLength = (strlen('* @param ') + $hintPadLength + $namePadLength);

            foreach ($parsedDescription['arguments'] AS $argument)
            {
                $paddedHint = str_pad($argument['hint'], $hintPadLength);
                $paddedName = str_pad($argument['name'], $namePadLength);
                $lineBreak = PHP_EOL . '     * ' . str_repeat(' ', $descriptionPadLength);

                $docBlock .= wordwrap(
                    "     * @param {$paddedHint} {$paddedName} {$argument['description']}",
                    95,
                    $lineBreak
                ) . PHP_EOL;
            }
        }

        $docBlock .= <<<TEXT
     */
TEXT;

        return $docBlock;
    }
}