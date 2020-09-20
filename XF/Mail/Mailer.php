<?php

namespace TickTackk\DeveloperTools\XF\Mail;

use TickTackk\DeveloperTools\Repository\EmailLog as EmailLogLog;
use \Swift_Mime_SimpleMessage as SwiftMimeSimpleMessage;
use \Swift_Mime_Message as SwiftMimeMessage;
use \Swift_Transport as SwiftTransport;

\class_alias(\XF::$versionId < 2020010 ? SwiftMimeMessage::class : SwiftMimeSimpleMessage::class, '\FinalSwiftMimeMessage');

/**
 * Class Mailer
 * 
 * Extends \XF\Mail\Mailer
 *
 * @package TickTackk\DeveloperTools\XF\Mail
 */
class Mailer extends XFCP_Mailer
{
    /**
     * @param \FinalSwiftMimeMessage $message
     * @param SwiftTransport|null $transport
     * @param array|null $queueEntry
     * @param bool $allowRetry
     *
     * @return int
     * @noinspection PhpSignatureMismatchDuringInheritanceInspection
     */
    public function send(\FinalSwiftMimeMessage $message, SwiftTransport $transport = null, array $queueEntry = null, $allowRetry = true)
    {
        $sent = parent::send($message, $transport, $queueEntry, $allowRetry);;

        if ($sent)
        {
            /** @var EmailLogLog $emailLogRepo */
            $emailLogRepo = \XF::app()->repository('TickTackk\DeveloperTools:EmailLog');
            $emailLogRepo->log($message);
        }

        return $sent;
    }
}