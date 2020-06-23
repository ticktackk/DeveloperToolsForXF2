<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use TickTackk\DeveloperTools\Entity\EmailLog as EmailLogEntity;
use XF\ControllerPlugin\Delete as DeleteControllerPlugin;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Mvc\Reply\Reroute as RerouteReply;
use XF\Mvc\Reply\Message as MessageReply;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Mvc\Reply\Error as ErrorReply;
use TickTackk\DeveloperTools\Repository\EmailLog as EmailLogRepo;

/**
 * Class Log
 * 
 * Extends \XF\Admin\Controller\Log
 *
 * @package TickTackk\DeveloperTools\XF\Admin\Controller
 */
class Log extends XFCP_Log
{
    /**
     * @param ParameterBag $parameterBag
     *
     * @return ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionEmail(ParameterBag $parameterBag) : AbstractReply
    {
        if ($parameterBag->email_id)
        {
            $emailLog = $this->assertEmailLogExists($parameterBag->email_id);

            $viewParams = [
                'emailLog' => $emailLog
            ];
            return $this->view(
                'TickTackk\DeveloperTools\XF:Log\Email\View',
                'tckDeveloperTools_log_email_view',
                $viewParams
            );
        }

        $page = $this->filterPage();
        $perPage = 100;

        $emailLogRepo = $this->getEmailLogRepo();
        $emailLogFinder = $emailLogRepo->findEmailLogForList()->limitByPage($page, $perPage);

        $viewParams = [
            'emailLogs' => $emailLogFinder->fetch(),

            'page' => $page,
            'perPage' => $perPage,

            'total' => $emailLogFinder->total()
        ];

        return $this->view(
            'TickTackk\DeveloperTools\XF:Log\Email\Listing',
            'tckDeveloperTools_log_email_list',
            $viewParams
        );
    }

    public function actionEmailClear() : AbstractReply
    {
        if ($this->isPost())
        {
            $this->getEmailLogRepo()->clearEmailLog();

            return $this->redirect($this->buildLink('logs/emails'));
        }

        return $this->view(
            'TickTackk\DeveloperTools\XF:Log\Email\Clear',
            'tckDeveloperTools_log_email_clear'
        );
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @return ErrorReply|RedirectReply|ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionEmailDelete(ParameterBag $parameterBag) : AbstractReply
    {
        $emailLog = $this->assertEmailLogExists($parameterBag->email_id);

        $email = \key($emailLog->to);
        $name = $emailLog->to[$email];

        /** @var DeleteControllerPlugin $deleteControllerPlugin */
        $deleteControllerPlugin = $this->plugin('XF:Delete');
        return $deleteControllerPlugin->actionDelete(
            $emailLog,
            $this->buildLink('logs/emails/delete', $emailLog),
            $this->buildLink('logs/emails', $emailLog),
            $this->buildLink('logs/emails'),
            "$emailLog->subject - $name ($email)"
        );
    }

    public function actionEmailRender(ParameterBag $parameterBag) : AbstractReply
    {
        $emailLog = $this->assertEmailLogExists($parameterBag->email_id);

        $viewParams = [
            'emailLog' => $emailLog
        ];
        $view = $this->view(
            'TickTackk\DeveloperTools\XF:Log\Email\Render',
            'tckDeveloperTools_log_email_render',
            $viewParams
        );
        $view->setViewOption('force_page_template', 'tckDeveloperTools_PAGE_EMAIL');

        return $view;
    }

    /**
     * @param int|null $emailId
     * @param array|string|null $with
     *
     * @return Entity|EmailLogEntity
     *
     * @throws ExceptionReply
     */
    protected function assertEmailLogExists(?int $emailId, $with = null) : EmailLogEntity
    {
        return $this->assertRecordExists(
            'TickTackk\DeveloperTools:EmailLog',
            $emailId, $with,
            'tckDeveloperTools_requested_email_not_found'
        );
    }

    /**
     * @return Repository|EmailLogRepo
     */
    protected function getEmailLogRepo() : EmailLogRepo
    {
        return $this->repository('TickTackk\DeveloperTools:EmailLog');
    }
}