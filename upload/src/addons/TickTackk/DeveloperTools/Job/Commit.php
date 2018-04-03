<?php

namespace TickTackk\DeveloperTools\Job;

use TickTackk\DeveloperTools\Git\GitRepository;
use XF\Job\AbstractJob;

class Commit extends AbstractJob
{
    public function run($maxRunTime)
    {
        $actionType = $this->data['actionType'];
        $typeDir = $this->data['typeDir'];
        $repoDir = $this->data['repoDir'];
        $entityClone = $this->data['entityClone'];
        $delayedJob = $this->data['delayedJob'];
        $isUpdate = $this->data['isUpdate'];

        if ($isUpdate && $actionType == 'export')
        {
            $actionType = 'change';
        }

        if ($delayedJob)
        {
            foreach ($entityClone as $key => $value)
            {
                if (strpos($value, '\__phrase') || (strcmp(substr($value, strlen($value) - strlen('\__phrase')), '\__phrase') === 0))
                {
                    $entityClone[$key] = \XF::phrase(str_replace('\__phrase', '', $value))->render();
                }
            }
        }

        if (is_dir($repoDir))
        {
            $git = new GitRepository($repoDir);

            $comment = \XF::phrase('developerTools_' . $actionType . '_' . $typeDir . '_commit_template', $entityClone)->render();

            $options = \XF::options();
            $gitUsername = $options->developerTools_git_username;
            $gitEmail = $options->developerTools_git_email;

            if (!empty($git->config()->get('user.name')))
            {
                $git->config()->add('user.name', $gitUsername)->execute();
            }
            if (!empty($git->config()->get('user.email')))
            {
                $git->config()->add('user.email', $gitEmail)->execute();
            }

            if (empty($git->config()->get('user.name')) || empty($git->config()->get('user.name')))
            {
                return $this->complete();
            }

            $git->add()->execute('*');

            $git->commit()
                ->message($comment)
                ->execute();
        }

        return $this->complete();
    }

    public function getStatusMessage()
    {
        return '';
    }

    public function canCancel()
    {
        return false;
    }

    public function canTriggerByChoice()
    {
        return false;
    }
}