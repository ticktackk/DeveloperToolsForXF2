<?php

namespace TickTackk\DeveloperTools\XF\Mail;

$destClass = 'TickTackk\DeveloperTools\XF\Mail\Mailer';
$srcClass = \XF::$versionId < 2020000
    ? 'TickTackk\DeveloperTools\XF\Mail\XF2\Mailer'
    : 'TickTackk\DeveloperTools\XF\Mail\XF22\Mailer';

class_alias($srcClass, $destClass);
class_alias(
    'TickTackk\DeveloperTools\XF\Mail\XFCP_Mailer',
    \XF::$versionId < 2020000 ? 'TickTackk\DeveloperTools\XF\Mail\XF22\XFCP_Mailer' : 'TickTackk\DeveloperTools\XF\Mail\XF2\XFCP_Mailer',
    false
);