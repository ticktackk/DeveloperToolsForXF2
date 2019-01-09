<?php

namespace TickTackk\DeveloperTools\Cli\Command\AddOn;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\Development\RequiresDevModeTrait;
use XF\Mvc\Entity\Entity;

/**
 * Class GenerateSchemaEntity
 *
 * @package TickTackk\DeveloperTools\Cli\Command\AddOn
 */
class GenerateSchemaEntity extends Command
{
    use RequiresDevModeTrait;

    protected function configure() : void
    {
        $this
            ->setName('ticktackk-devtools:generate-schema-entity')
            ->setDescription('Generates schema code from an entity')
            ->setAliases(['tdt:generate-schema-entity', 'tdt:schema-entity'])
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Identifier for the Entity (Prefix:Type format)'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getArgument('id');
        if (!$id || !preg_match('#^[a-z0-9_\\\\]+:[a-z0-9_\\\\]+$#i', $id))
        {
            $output->writeln('Identifier in the form of Prefix:Type must be provided.');

            return 1;
        }

        try
        {
            $entity = \XF::em()->create($id);
        }
        catch (\Exception $e)
        {
            $class = \XF::stringToClass($id, '%s\Entity\%s');
            $output->writeln("Entity class for $id ($class) could not be created.");

            return 2;
        }

        $structure = $entity->structure();

        $table = $structure->table;
        $primaryKey = $structure->primaryKey;
        $columns = $structure->columns;

        $primaryKeySet = false;
        $columnStrings = [];

        foreach ($columns AS $columnName => $column)
        {
            $length = $column['maxLength'] ?? null;
            $type = $this->resolveTypeDefaults($entity, $column['type'], $unsigned, $allowedDefault, $length);

            if ($length !== null)
            {
                if ($type === 'int')
                {
                    if ($length <= 255)
                    {
                        if ($length === 255)
                        {
                            $length = null;
                        }
                        $type = 'tinyint';
                    }
                    else if ($length <= 65536)
                    {
                        if ($length === 65536)
                        {
                            $length = null;
                        }
                        $type = 'shortint';
                    }
                    else if ($length > 4294967295)
                    {
                        if ($length === 4294967295)
                        {
                            $length = null;
                        }
                        $type = 'bigint';
                    }
                }
            }
            else
            {
                if ($type === 'varchar')
                {
                    $type = 'text';
                    $allowedDefault = false;
                }
            }

            $values = null;
            if (isset($column['allowedValues']))
            {
                $type = 'enum';
                if (count($column['allowedValues']) > 1)
                {
                    $values = '[\'' . implode('\', \'', $column['allowedValues']) . '\']';
                }
                else
                {
                    $values = '\'' . $column['allowedValues'] . '\'';
                }

                $length = null;
            }

            $string = '$this->addOrChangeColumn($table, \'' . $columnName . '\', \'' . $type . '\'' . ($length ? ', ' . $length : '') . ')';

            if ($values)
            {
                $string .= '->values(' . $values . ')';
            }

            if ($unsigned !== null)
            {
                if ($unsigned === false)
                {
                    $string .= '->unsigned(false)';
                }
            }

            if (!empty($column['nullable']))
            {
                $string .= '->nullable(true)';
            }

            if ($allowedDefault && isset($column['default']))
            {
                if ($column['default'] === \XF::$time)
                {
                    $default = 0;
                }
                else if (is_string($column['default']))
                {
                    $default = '\'' . $column['default'] . '\'';
                }
                else if (is_bool($column['default']))
                {
                    $default = ($column['default'] === true) ? 1 : 0;
                }
                else
                {
                    $default = $column['default'];
                }
                $string .= '->setDefault(' . $default . ')';
            }

            if (isset($column['autoIncrement']))
            {
                $string .= '->autoIncrement()';
                //$primaryKeySet = true;
            }

            $string .= ';';

            $columnStrings[] = $string;
        }

        $primaryKeyString = '';
        if (!$primaryKeySet && $primaryKey)
        {
            $primaryKeyString = "\n    ";
            if (is_array($primaryKey) && count($primaryKey) > 1)
            {
                $primaryKeyString .= '$table->addPrimaryKey([\'' . implode('\', \'', $primaryKey) . '\']);';
            }
            else
            {
                $primaryKeyString .= '$table->addPrimaryKey(\'' . $primaryKey . '\');';
            }
        }

        $columnOutput = implode("\n    ", $columnStrings);

        $sm = <<< FUNCTION
\$tables['$table'] = function (\$table) {
    /** @var Create|Alter $table */
    {$columnOutput}{$primaryKeyString}
});
FUNCTION;

        $output->writeln(['', $sm, '']);

        return 0;
    }

    /**
     * @param Entity    $entity
     * @param string    $type
     * @param bool|null $unsigned
     * @param bool      $allowedDefault
     * @param int|null  $length
     * @return string
     */
    protected function resolveTypeDefaults(Entity $entity, $type, &$unsigned = null, &$allowedDefault = true, &$length = null) : string
    {
        $unsigned = null;
        $allowedDefault = true;

        switch ($type)
        {
            case $entity::INT:
                $unsigned = false;

                return 'int';

            case $entity::UINT:
                return 'int';

            case $entity::FLOAT:
                return 'float';

            case $entity::BOOL:
                $length = 1;
                return 'tinyint';

            case $entity::STR:
                if($length)
                {
                    if ($length === 65535)
                    {
                        $length = null;
                        return 'text';
                    }
                    if ($length === 16777215)
                    {
                        $length = null;
                        return 'mediumtext';
                    }
                    if ($length === 4294967295)
                    {
                        $length = null;
                        return 'longtext';
                    }
                }
                return 'varchar';

            case $entity::BINARY:
                if($length)
                {
                    if ($length === 65535)
                    {
                        $length = null;
                        return 'blob';
                    }
                    if ($length === 16777215)
                    {
                        $length = null;
                        return 'mediumblob';
                    }
                    if ($length === 4294967295)
                    {
                        $length = null;
                        return 'longblob';
                    }
                }

                return 'varbinary';

            case $entity::SERIALIZED:
            case $entity::SERIALIZED_ARRAY:
            case $entity::JSON:
            case $entity::JSON_ARRAY:
            case $entity::LIST_LINES:
            case $entity::LIST_COMMA:
                $allowedDefault = false;

                return 'blob';
        }

        throw new \InvalidArgumentException('Could not infer type.');
    }
}