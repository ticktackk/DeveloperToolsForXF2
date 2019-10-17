<?php

namespace TickTackk\DeveloperTools\Util;

/**
 * Class AddOn
 *
 * @package TickTackk\DeveloperTools\Util
 */
class AddOn
{
    /**
     * @param string $class
     * @param string $formatter
     *
     * @return string
     */
    public static function classToString(string $class, string $formatter)
    {
        $parts = explode("\\$formatter\\", $class, 2);
        if (count($parts) == 1)
        {
            // already a string
            return $class;
        }

        return implode(':', $parts);
    }
}