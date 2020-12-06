<?php

namespace TickTackk\DeveloperTools\XF;

/**
 * Class PermissionCache
 *
 * @package TickTackk\DeveloperTools\XF
 */
class PermissionCache extends XFCP_PermissionCache
{
    /**
     * Returns extended permission set class which logs permission that do not exist but are being called
     *
     * @param int $permissionCombinationId
     * @return PermissionSet
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getPermissionSet($permissionCombinationId)
    {
        return new PermissionSet($this, $permissionCombinationId);
    }
}