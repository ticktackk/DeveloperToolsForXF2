<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use TickTackk\DeveloperTools\XF\Template\Templater as ExtendedTemplater;

/**
 * Class User
 * 
 * Extends \XF\Entity\User
 *
 * @package TickTackk\DeveloperTools\XF\Entity
 */
class User extends XFCP_User
{
    /**
     * @param string $group      The permission group in which the permission exists.
     * @param string $permission The specific permission we are looking for.
     *
     * @return bool|int Returns integer  if the permission is int if not then bool
     */
    public function hasPermission($group, $permission)
    {
        $globalPermissions = $this->PermissionSet->getGlobalPerms();

        if ($permission)
        {
            $groupEscaped = \XF::escapeString($group);
            $permissionEscaped = \XF::escapeString($permission);

            if (!\array_key_exists($group, $globalPermissions))
            {
                $this->logPermissionError("Permission group '$groupEscaped' is unknown for permission '$permissionEscaped'");
            }
            else if (!\array_key_exists($permission, $globalPermissions[$group]))
            {
                $this->logPermissionError("Permission '$permissionEscaped' is unknown in '$groupEscaped' permission group");
            }
        }

        return parent::hasPermission($group, $permission);
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
        $contentPermissions = $this->PermissionSet->getContentPerms($contentType, $contentId);

        $contentTypeEscaped = \XF::escapeString($contentType);
        $permissionEscaped = \XF::escapeString($permission);

        if ($contentPermissions)
        {
            if (!\array_key_exists($permission, $contentPermissions))
            {
                $this->logPermissionError("Permission '$permissionEscaped' unknown for content type '$contentTypeEscaped'");
            }
        }
        else
        {
            $this->logPermissionError("Content type '$contentTypeEscaped' is unknown for permission '$permissionEscaped'");
        }

        return parent::hasContentPermission($contentType, $contentId, $permission);
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
}