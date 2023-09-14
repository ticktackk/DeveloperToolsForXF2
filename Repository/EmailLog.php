<?php

namespace TickTackk\DeveloperTools\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use TickTackk\DeveloperTools\Finder\EmailLog as EmailLogFinder;
use Swift_Mime_SimpleMessage as SwiftMessage;
use XF\Mvc\Entity\Manager as EntityManager;
use TickTackk\DeveloperTools\Entity\EmailLog as EmailLogEntity;

/**
 * Class EmailLog
 *
 * @package TickTackk\DeveloperTools\Repository
 */
class EmailLog extends Repository
{
    public function findEmailLogForList() : EmailLogFinder
    {
        return $this->getEmailLogFinder()->setDefaultOrder('log_date', 'DESC');
    }

    /**
     * @param SwiftMessage $message
     *
     * @throws \XF\PrintableException
     */
    public function log(SwiftMessage $message) : void
    {
        /** @var EmailLogEntity $emailLog */
        $emailLog = $this->em()->create('TickTackk\DeveloperTools:EmailLog');

        $emailLog->bulkSet([
            'subject' => $message->getSubject(),
            'log_date' => time(),
            'return_path' => $message->getReturnPath(),
            'sender' => $message->getSender(),
            'from' => $message->getFrom(),
            'reply_to' => $message->getReplyTo(),
            'to' => $message->getTo(),
            'cc' => $message->getCc(),
            'bcc' => $message->getBcc()
        ]);

        $htmlMessageSet = false;
        $textMessageSet = false;

        foreach ($message->getChildren() AS $mimeEntity)
        {
            if (!$htmlMessageSet && $mimeEntity->getContentType() === 'text/html')
            {
                $emailLog->html_message = $mimeEntity->getBody();
                $htmlMessageSet = true;
            }

            if (!$textMessageSet && $mimeEntity->getContentType() === 'text/plain')
            {
                $emailLog->text_message = $mimeEntity->getBody();
                $textMessageSet = true;
            }

            if ($htmlMessageSet && $textMessageSet)
            {
                break;
            }
        }

        $emailLog->save();
    }

    public function clearEmailLog() : void
    {
        $this->db()->emptyTable('xf_tck_developer_tools_email_log');
    }

    /**
     * @return Finder|EmailLogFinder
     */
    public function getEmailLogFinder() : EmailLogFinder
    {
        return $this->finder('TickTackk\DeveloperTools:EmailLog');
    }

    protected function em() : EntityManager
    {
        return $this->em;
    }
}