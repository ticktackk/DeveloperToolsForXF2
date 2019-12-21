var TickTackk = window.TickTackk || {};
TickTackk.DeveloperTools = TickTackk.DeveloperTools || {};

(function ($, window, document, _undefined)
{
    'use strict';

    /**
     * Click event handler to add phrase block
     */
    TickTackk.DeveloperTools.AddMorePhrase = XF.Event.newHandler({
        eventNameSpace: 'TickTackkDeveloperToolsAddMorePhraseClick',

        options: {
            languageId: null,

            languageSeparatorSelector: '#language_separator',
            newPhraseBlockSelector: '#new_phrase_block'
        },

        $languageSeparator: null,
        $newPhraseBlock: null,

        /**
         * Initializes the required elements. If any of the options result in failure, the click event will not be called.
         */
        init: function ()
        {
            if (this.options.languageId === null)
            {
                console.error('No language id has been provided.');
                return;
            }

            var $languageSeparator = $(this.options.languageSeparatorSelector);
            if (!$languageSeparator.length)
            {
                console.log('No language separator is available.');
                return;
            }
            this.$languageSeparator = $languageSeparator;

            var $newPhraseBlock = $(this.options.newPhraseBlockSelector);
            if (!$newPhraseBlock.length)
            {
                console.log('No new phrase block is available.');
                return;
            }
            this.$newPhraseBlock = $newPhraseBlock.parent();
        },

        /**
         * This is the real click event.
         *
         * @param e The event
         */
        click: function (e)
        {
            var $newPhraseBlock = this.$newPhraseBlock,
                currentPhraseCount = this.$newPhraseBlock.parent().find('hr[data-for-more-phrase="true"]').length;

            XF.ajax('get', 'admin.php?phrases/more-phrase-block', {
                language_id: this.options.languageId,
                current_phrase_count: currentPhraseCount
            }, function (data)
            {
                if (data.html)
                {
                    XF.setupHtmlInsert(data.html, function($html, container)
                    {
                        $html.appendTo($newPhraseBlock);
                    });
                }
            });
        }
    });

    XF.Event.register('click', 'new-phrase-block-adder', 'TickTackk.DeveloperTools.AddMorePhrase');
})(jQuery, window, document);