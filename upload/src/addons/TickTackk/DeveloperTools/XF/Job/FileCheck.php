<?php

namespace NF\XDT\TickTackk\DeveloperTools;



/**
 * Extends \XF\Job\FileCheck
 */
class FileCheck extends XFCP_FileCheck
{
    public function run($maxRunTime)
    {
        if (\XF::options()->developerTools_HashCheckDisable)
        {
            /** @var \XF\Entity\FileCheck $fileCheck */
            $fileCheck = $this->app->em()->find('XF:FileCheck', $this->data['check_id']);
            if (!$fileCheck)
            {
                throw new \InvalidArgumentException('Cannot perform a file health check without an associated file check record.');
            }

            $results = [
                'missing' => [],
                'inconsistent' => [],
                'total_missing' => 0,
                'total_inconsistent' => 0,
                'total_checked' => 0,
            ];

            $options = $this->app->options();
            $options->emailFileCheckWarning['enabled'] = false;

            $this->completeFileCheck($fileCheck, $results);

            return $this->complete();
        }

        return parent::run($maxRunTime);
    }
}
