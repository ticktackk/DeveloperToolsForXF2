<?php

namespace TickTackk\DeveloperTools\XF\Mail;

use TickTackk\DeveloperTools\Repository\EmailLog as EmailLogLog;
use XF\App as BaseApp;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Service\AbstractService;
use XF\Mvc\Entity\Manager as EntityManager;
use XF\Job\Manager as JobManager;
use \Swift_Mime_Message as SwiftMessage;
use \Swift_Transport as SwiftTransport;

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
     * @param SwiftMessage $message
     * @param SwiftTransport|null $transport
     * @param array|null $queueEntry
     * @param bool $allowRetry
     *
     * @return int
     */
    public function send(SwiftMessage $message, SwiftTransport $transport = null, array $queueEntry = null, $allowRetry = true)
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