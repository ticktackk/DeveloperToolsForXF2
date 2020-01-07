var TickTackk = window.TickTackk || {};
TickTackk.DeveloperTools = TickTackk.DeveloperTools || {};

(function ($, window, document, _undefined)
{
    "use strict";

    $(document).on('ajax:send', function (e, xhr, settings)
    {
        var realOnSuccess = settings.success;

        settings.success = function (data, status, xhr)
        {
            if (data.html.permissionErrors)
            {
                var permissionErrorStr = 'Permission errors were triggered when rendering this template:';
                if (data.html.permissionErrorDetails)
                {
                    permissionErrorStr += '\n* ' + data.html.permissionErrorDetails.join('\n* ');
                }
                console.error(permissionErrorStr)
            }

            if (realOnSuccess)
            {
                return realOnSuccess(data, status, xhr);
            }
        };
    });
})(jQuery, window, document);