<?php

namespace TickTackk\DeveloperTools\XF\Repository;

use TickTackk\DeveloperTools\XF\Repository\Exception\CodeEventNotFoundException;
use XF\Entity\CodeEvent as CodeEventEntity;

use function count;

/**
 * Class CodeEvent
 * 
 * Extends \XF\Repository\CodeEvent
 *
 * @package TickTackk\DeveloperTools\XF\Repository
 */
class CodeEvent extends XFCP_CodeEvent
{
    /**
     * This is used to create a doc block for code events
     *
     * @param string $eventId The event id for which doc block must be created.
     * @param string|null $callbackSignature The callback signature
     *
     * @return string The doc block of the event
     */
    public function getDocBlockForCodeEvent(string $eventId, string &$callbackSignature = null) : string
    {
        /** @var CodeEventEntity $codeEvent */
        $codeEvent = $this->app()->find($this->identifier, $eventId);
        if (!$codeEvent)
        {
            throw new CodeEventNotFoundException($eventId);
        }

        $docBlock = "\t/**";
        $descriptionParts = array_filter(explode('<p>', str_ireplace([
            '<code>',
            '</code>'
        ], '', $codeEvent->description)));

        foreach ($descriptionParts AS $descriptionPartIndex => $descriptionPart)
        {
            $descriptionPart = utf8_trim($descriptionPart);
            $descriptionParts[$descriptionPartIndex] = $descriptionPart;

            if (utf8_strlen($descriptionPart) > 4 && utf8_substr($descriptionPart, utf8_strlen($descriptionPart) - 4) === '</p>')
            {
                $docBlock .= "\n\t * " . wordwrap(strip_tags(utf8_rtrim($descriptionPart, '</p>')), 85, "\n\t * ") . PHP_EOL;
                unset($descriptionParts[$descriptionPartIndex]);
            }
        }

        $parameters = [];
        $typePad = 0;
        $namePad = 0;

        $addParameter = function (string $parameterName, string $parameterDescription, string $parameterType = 'mixed') use(&$parameters, &$typePad, &$namePad)
        {
            $parameters[] = [
                'type' => $parameterType,
                'name' => html_entity_decode($parameterName),
                'description' => html_entity_decode($parameterDescription)
            ];

            $typeLength = utf8_strlen($parameterType);
            if ($typeLength > $typePad)
            {
                $typePad = $typeLength;
            }

            $nameLength = utf8_strlen($parameterName);
            if ($nameLength > $namePad)
            {
                $namePad = $nameLength;
            }
        };

        preg_match('#<p>Arguments:<\/p>.<ol>(.*?)<\/ol>#si', $codeEvent->description, $matches);

        $parametersBlock = utf8_trim($matches[1] ?? ''); // trim LOL
        $parameterLiCollection = array_map(function ($li)
        {
            return utf8_substr(utf8_rtrim(utf8_ltrim(utf8_trim($li), '<li'), '</li>'), 1);
        }, explode(PHP_EOL, $parametersBlock)); // get cleaned <li> tags

        foreach ($parameterLiCollection AS $parameterLi)
        {
            $parameterArr = explode(' - ', $parameterLi);
            if (count($parameterArr) === 2)
            {
                $parameterDescription = $parameterArr[1];

                if (utf8_strpos($parameterArr[0], '<em>') === 0)
                {
                    [$parameterType, $parameterName] = explode('</em>', $parameterArr[0]);
                    $parameterType = utf8_ltrim($parameterType, '<em>');
                }
                else
                {
                    $parameterType = '';
                    $parameterName = $parameterArr[0];
                }

                $parameterType = strip_tags($parameterType);
                $parameterName = strip_tags($parameterName);

                $parameterName = utf8_trim($parameterName);
                $addParameter($parameterName, $parameterDescription, $parameterType);
            }
        }

        if ($callbackSignature === null)
        {
            $callbackSignature = '';
        }

        foreach ($parameters AS $parameter)
        {
            $type = $parameter['type'];
            $name = $parameter['name'];

            if ($type)
            {
                $callbackSignature .= " {$type} $name,";
            }
            else
            {
                $callbackSignature .= " {$name},";
            }

            $type = str_pad($type, $typePad);
            $name = str_pad($name, $namePad);

            $linePrefix = "\n\t * @param {$type} {$name}";

            $description = wordwrap($parameter['description'], 85, "\n\t" . str_repeat(' ', utf8_strlen($linePrefix) - 1));

            $docBlock .= $linePrefix . " {$description}";
        }

        $callbackSignature = rtrim(utf8_trim($callbackSignature), ',');

        return utf8_rtrim($docBlock, PHP_EOL) . PHP_EOL . "\t */";
    }
}