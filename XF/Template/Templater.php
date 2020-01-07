<?php

namespace TickTackk\DeveloperTools\XF\Template;

use XF\Template\WatcherInterface;

/**
 * Class Templater
 *
 * Extends \XF\Template\Templater
 *
 * @package TickTackk\DeveloperTools\XF\Template
 */
class Templater extends XFCP_Templater
{
    /**
     * Stores all permission errors that have been logged here
     *
     * @var array
     */
    protected $permissionErrors = [];

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     *
     * @param WatcherInterface $watcher
     */
    public function addTemplateWatcher(WatcherInterface $watcher)
    {
        if (\XF::options()->developerTools_TemplaterWatchDisable)
        {
            return;
        }

        parent::addTemplateWatcher($watcher);
    }

    /**
     * Returns all logged permission errors
     *
     * @return array
     */
    public function getPermissionErrors() : array
    {
        return $this->permissionErrors;
    }

    /**
     * Logs permission error
     *
     * @param string $error Complete error information
     * @param string $file  The file where the invalid permission/permission group was called from
     * @param string $line  The line where the invalid permission/permission group was called
     */
    public function logPermissionError(string $error, string $file, string $line) : void
    {
        $this->permissionErrors[] = [
            'error' => $error,
            'file' => $file,
            'line' => $line
        ];
    }
}