<?php

namespace TickTackk\DeveloperTools\Service\CodeEvent;

use Symfony\Component\DomCrawler\Crawler as DOMCrawler;
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

    /**
     * @return string
     */
    protected function parseDescription() : string
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

    /**
     * @return string
     */
    protected function parseEventHint() : string
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
        return \trim($eventHintNode->nodeValue);
    }

    /**
     * @param string $hint
     *
     * @return string
     */
    protected function getFinalHint(string $hint)
    {
        switch ($hint)
        {
            case 'boolean':
                return 'bool';

            case 'integer':
                return 'int';

            case '':
                return 'mixed';
        }

        return $hint;
    }

    /**
     * @return array
     */
    protected function parseArguments() : array
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
            $name = \trim($nameNode->nodeValue);

            $listItem->removeChild($nameNode); // remove the code element
            $description = $listItem->nodeValue;

            // if not then we don't care because this unwritten standard
            if (\substr($description, 0, 3) === ' - ')
            {
                $description = \substr($description, 3);
            }
            else
            {
                $description = '';
            }

            $passedByRef = \substr($name, 0, 1) === '&';
            if ($passedByRef)
            {
                $name = substr($name, 1);
            }

            $arguments[] = [
                'hint' => $this->getFinalHint($hintType),
                'name' => $name,
                'passedByRef' => $passedByRef,
                'description' => \ucfirst($description)
            ];
        }

        return $arguments;
    }

    /**
     * @return array
     */
    public function parse() : array
    {
        return [
            'description' => $this->parseDescription(),
            'eventHint' => $this->parseEventHint(),
            'arguments' => $this->parseArguments()
        ];
    }
}