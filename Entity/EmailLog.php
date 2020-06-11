<?php

namespace TickTackk\DeveloperTools\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure as EntityStructure;

/**
 * Class EmailLog
 *
 * @package TickTackk\DeveloperTools\Entity
 *
 * COLUMNS
 * @property int|null email_id
 * @property string subject
 * @property int log_date
 * @property string return_path
 * @property array<string, string> sender
 * @property array<string, string> from
 * @property string[] reply_to
 * @property string[] to
 * @property string[] cc
 * @property string[] bcc
 * @property string html_message
 * @property string text_message
 */
class EmailLog extends Entity
{
    public static function getStructure(EntityStructure $structure) : EntityStructure
    {
        $structure->shortName = 'TickTackk\DeveloperTools:EmailLog';
        $structure->table = 'xf_tck_developer_tools_email_log';
        $structure->primaryKey = 'email_id';
        $structure->columns = [
            'email_id' => ['type' => static::UINT, 'autoIncrement' => true, 'nullable' => true],
            'subject' => ['type' => static::STR, 'required' => true],
            'log_date' => ['type' => static::UINT, 'required' => true],
            'return_path' => ['type' => static::STR, 'required' => true],
            'sender' => ['type' => static::JSON, 'default' => null, 'nullable' => true],
            'from' => ['type' => static::JSON, 'required' => true],
            'reply_to' => ['type' => static::JSON, 'default' => null, 'nullable' => true],
            'to' => ['type' => static::JSON, 'required' => true],
            'cc' => ['type' => static::JSON, 'default' => null, 'nullable' => true],
            'bcc' => ['type' => static::JSON, 'default' => null, 'nullable' => true],
            'html_message' => ['type' => static::STR, 'default' => null, 'nullable' => true],
            'text_message' => ['type' => static::STR, 'default' => null, 'nullable' => true],
        ];

        return $structure;
    }
}