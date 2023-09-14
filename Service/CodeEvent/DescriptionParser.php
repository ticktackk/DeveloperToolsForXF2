<?php

namespace TickTackk\DeveloperTools\Service\CodeEvent;

use Symfony\Component\DomCrawler\Crawler as DOMCrawler;
use XF\AddOn\AddOn;
use XF\Entity\CodeEvent as CodeEventEntity;
use XF\Service\AbstractService;
use XF\App as BaseApp;
use DOMNode;
use DOMElement;

/**
 * Class DescriptionParser
 *
 * @package TickTackk\DeveloperTools\Service\CodeEvent
 */
class DescriptionParser extends AbstractService
{
    /**
     * @var CodeEventEntity
     */
    protected $codeEvent;

    /**
     * DescriptionParser constructor.
     *
     * @param BaseApp $app
     * @param CodeEventEntity $codeEvent
     */
    public function __construct(BaseApp $app, CodeEventEntity $codeEvent)
    {
        parent::__construct($app);

        $this->codeEvent = $codeEvent;
    }

    /**
     * @return CodeEventEntity
     */
    public function getCodeEvent() : CodeEventEntity
    {
        return $this->codeEvent;
    }

    /**
     * @param string|DOMNode $html
     *
     * @return DOMCrawler
     */
    protected function getDOMCrawler($html) : DOMCrawler
    {
        return new DOMCrawler($html);
    }

    /**
     * @return DOMCrawler
     */
    protected function getCodeEventDOMCrawler()
    {
        $codeEvent = $this->getCodeEvent();

        return $this->getDOMCrawler($codeEvent->description);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function parseDescription(AddOn $addOn) : string
    {
        $codeEventDOMCrawler = $this->getCodeEventDOMCrawler();

        $descriptionNodes = $codeEventDOMCrawler->filter('p');
        if (!$descriptionNodes->count())
        {
            return '';
        }

        return $descriptionNodes
            ->first()
            ->getNode(0)
            ->nodeValue;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function parseEventHint(AddOn $addOn) : string
    {
        $codeEventDOMCrawler = $this->getCodeEventDOMCrawler();

        $eventHintNodes = $codeEventDOMCrawler->filter('ol + p');
        if (!$eventHintNodes->count())
        {
            return '';
        }

        $eventHintNode = $eventHintNodes->first()->getNode(0);
        $eventHintLabelNodes = $this->getDOMCrawler($eventHintNode)->filter('b');
        if (!$eventHintLabelNodes->count())
        {
            return '';
        }

        $eventHintNode->removeChild($eventHintLabelNodes->first()->getNode(0));
        return trim($eventHintNode->nodeValue);
    }

    protected function getMinPhpRequire(AddOn $addOn) : string
    {
        $requirePhpversion = '5.6.0';
        $json = $addOn->getJson();

        if (isset($json['require']['XF']))
        {
            $requireXfVersion = $json['require']['XF'];
            if ($requireXfVersion[0] >= 2020010)
            {
                $requirePhpversion = '7.0.0';
            }
        }

        if (isset($json['require']['php']))
        {
            $requirePhpversion = $json['require']['php'][0];
        }

        return $requirePhpversion;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function getFinalHint(string $hint, AddOn $addOn)
    {
        switch ($hint)
        {
            case 'boolean':
                return 'bool';

            case 'integer':
                return 'int';

            case '':
                return '';
        }

        return $hint;
    }

    protected function parseArguments(AddOn $addOn) : array
    {
        $codeEventDOMCrawler = $this->getCodeEventDOMCrawler();
        $arguments = [];

        /** @var DOMElement $listItem */
        foreach ($codeEventDOMCrawler->filter('p + ol > li') AS $listItem)
        {
            $listItemHtmlCrawler = new DOMCrawler($listItem);

            $hintTypeNodes = $listItemHtmlCrawler->filter('code > em');
            if ($hintTypeNodes->count() > 1) // only 1 hint
            {
                continue;
            }

            $hintTypeNode = $hintTypeNodes->count() ? $hintTypeNodes->first()->getNode(0) : null;
            $hintType = $hintTypeNode ? $hintTypeNode->nodeValue : '';

            $nameNodes = $listItemHtmlCrawler->filter('code');
            if ($nameNodes->count() !== 1)
            {
                continue;
            }

            $nameNode = $nameNodes->getNode(0);
            if ($hintTypeNode)
            {
                $nameNode->removeChild($hintTypeNode);
            }
            $name = trim($nameNode->nodeValue);

            $listItem->removeChild($nameNode); // remove the code element
            $description = $listItem->nodeValue;

            // if not then we don't care because this unwritten standard
            if (substr($description, 0, 3) === ' - ')
            {
                $description = substr($description, 3);
            }
            else
            {
                $description = '';
            }

            $passedByRef = substr($name, 0, 1) === '&';
            if ($passedByRef)
            {
                $name = substr($name, 1);
            }

            $arguments[] = [
                'hint' => $this->getFinalHint($hintType, $addOn),
                'name' => substr($name, 1), // Removes $
                'passedByRef' => $passedByRef,
                'description' => ucfirst($description)
            ];
        }

        return $arguments;
    }

    /**
     * @param AddOn $addOn
     *
     * @return array
     */
    public function parse(AddOn $addOn) : array
    {
        return [
            'description' => $this->parseDescription($addOn),
            'eventHint' => $this->parseEventHint($addOn),
            'arguments' => $this->parseArguments($addOn),
            'returnType' => version_compare($this->getMinPhpRequire($addOn), '7.1.0', '>=') ? 'void' : null
        ];
    }
}