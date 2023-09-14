<?php

namespace TickTackk\DeveloperTools\XF\Mail\XF2;

use TickTackk\DeveloperTools\Repository\EmailLog as EmailLogLog;
use TickTackk\DeveloperTools\XF\Mail\XFCP_Mailer;

class Mailer extends XFCP_Mailer
{
    /**
     * @param \Swift_Mime_Message $message
     * @param \Swift_Transport|null $transport
     * @param array|null $queueEntry
     * @param $allowRetry
     *
     * @return int
     *
     * @throws \XF\PrintableException
     *
     * @noinspection PhpUndefinedClassInspection
     */
    public function send
    (
        \Swift_Mime_Message $message,
        \Swift_Transport $transport = null,
        array $queueEntry = null,
        $allowRetry = true
    )
    {
        $sent = parent::send($message, $transport, $queueEntry, $allowRetry);

        if ($sent)
        {
            /** @var EmailLogLog $emailLogRepo */
            $emailLogRepo = \XF::app()->repository('TickTackk\DeveloperTools:EmailLog');
            $emailLogRepo->log($message);
        }

        return $sent;
    }
}