var TickTackk = window.TickTackk || {};
TickTackk.DeveloperTools = TickTackk.DeveloperTools || {};

(function ($, window, document, _undefined)
{
    'use strict';

    /**
     * This helps in creating code event listeners fast enough
     */
    TickTackk.DeveloperTools.CodeEventFieldLoader = XF.Element.newHandler({
        $container: null,

        $callbackClassInput: null,
        $callbackMethodInput: null,

        $addOnSelect: null,

        /**
         * Sets the container (block body), callback class input, callback method input, add-on select and finally
         * makes so every time event id or add-on id is changed required inputs are updated accordingly.
         */
        init: function ()
        {
            this.$container = this.$target.closest('form');
            if (!this.$container.length)
            {
                console.error('Unable to find container of the event select.');
                return;
            }

            this.$callbackClassInput = this.$container.find('[name="callback_class"]');
            if (!this.$callbackClassInput.length)
            {
                console.error('Unable to find callback class input.');
                return;
            }

            this.$callbackMethodInput = this.$container.find('[name="callback_method"]');
            if (!this.$callbackMethodInput.length)
            {
                console.error('Unable to find callback class input.');
                return;
            }

            this.$addOnSelect = this.$container.find('[name="addon_id"]');
            if (!this.$addOnSelect.length)
            {
                console.error('Unable to find add-on select menu.');
                return;
            }

            this.$target.on('change', XF.proxy(this, 'setInputs'));
            this.$addOnSelect.on('change', XF.proxy(this, 'setInputs'));
        },

        /**
         * This updates callback class input and callback method input every time event id or add-on is changed
         */
        setInputs: function ()
        {
            var selectedEvent = this.$target.val();
            if (selectedEvent !== '')
            {
                var selectedAddOnId = this.getSelectedAddOnId(),
                    callbackClass = this.getCallbackClass(selectedAddOnId);
                this.$callbackClassInput.val(callbackClass);

                var callbackMethod = this.getCallbackMethodFromEventId(selectedEvent);
                this.$callbackMethodInput.val(callbackMethod);
            }
        },

        /**
         * Returns the selected add-on id
         *
         * @returns {null|string} Will return null if no add-on is selected
         */
        getSelectedAddOnId: function ()
        {
            var addOnId = this.$addOnSelect.val();

            if (addOnId === '')
            {
                return null;
            }

            return addOnId;
        },

        /**
         * Returns the callback class while replacing forward slash with backslash
         *
         * @param {string} addOnId The selected add-on id
         * @returns {string} This will return empty string if add-on id is null
         */
        getCallbackClass: function (addOnId)
        {
            if (addOnId === null)
            {
                return '';
            }

            return addOnId.replace('/', '\\') + '\\Listener';
        },

        /**
         * Returns event id in camel case
         *
         * @param {string} eventId The selected event id
         * @returns {string} This will return empty string if no event id is selected.
         */
        getCallbackMethodFromEventId: function (eventId)
        {
            eventId = eventId.toLowerCase().trim();

            if (eventId === '')
            {
                return '';
            }

            return eventId.replace(/[^a-zA-Z0-9]+(.)/g, function (match, characterData)
            {
                return characterData.toUpperCase();
            });
        }
    });

    XF.Element.register('code-event-field-loader', 'TickTackk.DeveloperTools.CodeEventFieldLoader');
})(jQuery, window, document);