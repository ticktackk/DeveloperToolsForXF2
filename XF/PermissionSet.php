<?php

namespace TickTackk\DeveloperTools\XF;

use TickTackk\DeveloperTools\XF\Template\Templater as ExtendedTemplater;
use XF\App as BaseApp;
use XF\PermissionSet as BasePermissionSet;

/**
 * Class PermissionSet
 *
 * @package TickTackk\DeveloperTools\XF
 */
class PermissionSet extends BasePermissionSet
{
    /**
     * @param string $group      The permission group in which the permission exists.
     * @param string $permission The specific permission we are looking for.
     *
     * @return bool|int Returns integer  if the permission is int if not then bool
     */
    public function hasGlobalPermission($group, $permission)
    {
        $permissionCache = $this->getPermissionCache();
        $permissionCombinationId = $this->getPermissionCombinationId();
        $permissions = $permissionCache->getGlobalPerms($permissionCombinationId);

        if (!$permissions)
        {
            return false;
        }

        $groupEscaped = \XF::escapeString($group);
        $permissionEscaped = \XF::escapeString($permission);

        if (!\array_key_exists($group, $permissions))
        {
            $this->logPermissionError("Permission group '$groupEscaped' is unknown for permission '$permissionEscaped'");
        }
        else if (!\array_key_exists($permission, $permissions[$group]))
        {
            $this->logPermissionError("Permission '$permissionEscaped' is unknown in '$groupEscaped' permission group");
        }

        if (!isset($permissions[$group][$permission]))
        {
            return false;
        }

        return $permissions[$group][$permission];
    }

    /**
     * @param string $contentType The content type of content aka node/category/etc.
     * @param int    $contentId   The content id of specific node/category/etc
     * @param string $permission  The specific permission we are looking for.
     *
     * @return bool|int Returns integer  if the permission is int if not then bool
     */
    public function hasContentPermission($contentType, $contentId, $permission)
    {
        $permissionCache = $this->getPermissionCache();
        $permissionCombinationId = $this->getPermissionCombinationId();
        $permissions = $permissionCache->getContentPerms($permissionCombinationId, $contentType, $contentId);

        $contentTypeEscaped = \XF::escapeString($contentType);
        $permissionEscaped = \XF::escapeString($permission);

        if ($permissions)
        {
            if (!\array_key_exists($permission, $permissions))
            {
                $this->logPermissionError("Permission '$permissionEscaped' unknown for content type '$contentTypeEscaped'");
            }
        }
        else
        {
            $this->logPermissionError("Content type '$contentTypeEscaped' is unknown for permission '$permissionEscaped'");
        }

        if (!$permissions || !isset($permissions[$permission]))
        {
            return false;
        }

        return $permissions[$permission];
    }

    /**
     * Logs permission error
     *
     * @param string $error Complete error information
     */
    private function logPermissionError(string $error) : void
    {
        $backtrace = debug_backtrace()[1];

        /** @var ExtendedTemplater $templater */
        $templater = $this->app()->templater();
        $templater->logPermissionError($error, $backtrace['file'], $backtrace['line']);
    }

    /**
     * @return BaseApp
     */
    protected function app() : BaseApp
    {
        return \XF::app();
    }
}