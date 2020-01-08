<?php

namespace TickTackk\DeveloperTools\XF;

use XF\PermissionCache as BasePermissionCache;
use XF\PermissionSet as BasePermissionSet;
use TickTackk\DeveloperTools\XF\PermissionSet as ExtendedPermissionSet;

/**
 * Class PermissionCache
 *
 * @package TickTackk\DeveloperTools\XF
 */
class PermissionCache extends BasePermissionCache
{
    /**
     * Returns extended permission set class which logs permission that do not exist but are being called
     *
     * @param int $permissionCombinationId
     *
     * @return PermissionSet|BasePermissionSet
     */
    public function getPermissionSet($permissionCombinationId)
    {
        return new ExtendedPermissionSet($this, $permissionCombinationId);
    }
}