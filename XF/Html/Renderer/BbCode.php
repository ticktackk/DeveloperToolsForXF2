<?php

namespace TickTackk\DeveloperTools\XF\Html\Renderer;

use XF\Html\Tag as HtmlTag;

/**
 * Class BbCode
 * 
 * Extends \XF\Html\Renderer\BbCode
 *
 * @package TickTackk\DeveloperTools\XF\Html\Renderer
 */
class BbCode extends XFCP_BbCode
{
    /**
     * BbCode constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (\XF::$versionId < 2011070)
        {
            $handleCodeTag = $options['handleCodeTagForTckDeveloperTools'] ?? false;
            unset($options['handleCodeTagForTckDeveloperTools']);
        }

        parent::__construct($options);

        if (\XF::$versionId < 2011070 && $handleCodeTag && !\array_key_exists('code', $this->_handlers))
        {
            $this->_handlers['code'] = [
                'filterCallback' => ['$this', 'handleTagCodeForTckDeveloperTools'],
                'skipCss' => true
            ];
        }
    }

    /**
     * Handles inline code and code tags.
     *
     * @param string $text
     * @param HtmlTag $tag
     *
     * @return string
     */
    public function handleTagCodeForTckDeveloperTools(string $text, HtmlTag $tag) : string
    {
        if (\preg_match('#\r\n|\r|\n#', $text))
        {
            return '[CODE]' . $text . '[/CODE]';
        }

        return '[ICODE]' . $text . '[/ICODE]';
    }
}