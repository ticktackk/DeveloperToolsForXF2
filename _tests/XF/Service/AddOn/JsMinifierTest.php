<?php

namespace TickTackk\DeveloperTools\XF\Service\AddOn;

use TickTackk\DeveloperTools\Test\Service\AbstractTestCase;
use TickTackk\DeveloperTools\Test\TestXF;
use TickTackk\DeveloperTools\XF\Service\AddOn\Exception\ClosureCompilerNotFoundException;

/**
 * Class JsMinifierTest
 *
 * @package TickTackk\DeveloperTools\XF\Service\AddOn
 */
class JsMinifierTest extends AbstractTestCase
{
    private $jsFilePath;

    private $jsContents;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jsFilePath = $this->getWorkingDirPath() . TestXF::$DS . 'js_to_minify.js';
        $this->jsContents = <<<EOS
var TickTackk = window.TickTackk || {};
TickTackk.XDT = TickTackk.XDT || {};

(function ($, window, document, _undefined)
{
    "use strict";

    TickTackk.XDT.Sample = XF.Element.newHandler({
        options: {
            optionOne: null,
            optionTwo: true,
            optionThree: 69
        },

        init: function ()
        {
            if (this.options.optionOne === null)
            {
                this.options.optionOne = 'option-one';
            }
        },
    });

    XF.Element.register('sv-multi-prefix-menu', 'SV.MultiPrefix.PrefixMenu');
}(jQuery, window, document));
EOS;

        $this->writeToFile($this->jsFilePath, $this->jsContents);
    }

    /**
     * @throws ClosureCompilerNotFoundException
     * @throws \ErrorException
     */
    public function testClosureCompilerNotFound()
    {
        $this->app()->config = array_merge_recursive($this->app()->config(), [
            'development' => [
                'closureJar' => $this->getWorkingDirPath() . TestXF::$DS . 'tck-xdt-fake-closure-compiler.jar'
            ]
        ]);

        /** @var JsMinifier $service */
        $service = $this->app()->service(
            'XF:AddOn\JsMinifier',
            $this->jsFilePath
        );
        $this->expectException(ClosureCompilerNotFoundException::class);
        $service->minify();
    }
}