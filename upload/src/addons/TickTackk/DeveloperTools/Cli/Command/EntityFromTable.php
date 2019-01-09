<?php

namespace TickTackk\DeveloperTools\Cli\Command\AddOn;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use XF\Cli\Command\AddOnActionTrait;
use XF\Cli\Command\Development\RequiresDevModeTrait;
use XF\Util\File;

/**
 * Class EntityFromTable
 *
 * @package TickTackk\DeveloperTools\Cli\Command\AddOn
 */
class EntityFromTable extends Command
{
    use AddOnActionTrait, RequiresDevModeTrait;

    protected function configure() : void
    {
        $this
            ->setName('ticktackk-devtools:create-entity-from-table')
            ->setDescription('Creates an XF entity for an add-on from a table.')
            ->setAliases(['tdt:create-entity-from-table', 'tdt:create-entity'])
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            )
            ->addArgument(
                'table',
                InputArgument::REQUIRED,
                'table to inspect'
            )
            ->addArgument(
                'RelationName',
                InputArgument::REQUIRED,
                'The entity\'s name'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force writing out entity file even if it exists'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $addOnId = $input->getArgument('id');
        if (!$addOnId)
        {
            $question = new Question('<question>Enter the ID for the add-on:</question> ');
            $question->setValidator($this->getAddOnQuestionFieldValidator('addon_id'));
            $addOnId = $helper->ask($input, $output, $question);
            $output->writeln('');
        }

        $manager = \XF::app()->addOnManager();
        $addOn = $manager->getById($addOnId);
        if (!$addOn || !$addOn->isAvailable())
        {
            $output->writeln('Add-on could not be found.');

            return 1;
        }

        $table = $input->getArgument('table');
        if (!$table)
        {
            $question = new Question('<question>Enter the table to extend:</question> ');
            $table = $helper->ask($input, $output, $question);
            $output->writeln('');
        }

        $name = $input->getArgument('RelationName');
        if (!$name)
        {
            $question = new Question('<question>Enter the relationship name for the entity:</question> ');
            $name = $helper->ask($input, $output, $question);
            $output->writeln('');
        }

        $addOnId = $addOn->getAddOnId();
        $filename = $manager->getAddOnPath($addOnId) . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR . $name . '.php';
        $force = $input->getOption('force');

        if (\file_exists($filename))
        {
            if ($force)
            {
                $output->writeln("<warning>The file {$filename} already exists, overwriting!</warning>");
            }
            else
            {
                $output->writeln("<error>The file {$filename} already exists</error>");
                return 1;
            }
        }

        $sm = \XF::db()->getSchemaManager();

        $tableColDefinition = $sm->getTableColumnDefinitions($table);
        if (!$tableColDefinition)
        {
            $output->writeln("<error>The table {$table} does not exists");

            return 1;
        }
        $tableIndexDefinition = $sm->getTableIndexDefinitions($table);

        $namespace = str_replace('/', '\\', $addOnId);

        if (empty($tableIndexDefinition['PRIMARY']))
        {
            $output->writeln("<error>The table {$table} does not have a primary key!");

            return 1;
        }

        $primaryKey = [];
        foreach ($tableIndexDefinition['PRIMARY'] as $column)
        {
            $primaryKey[] = var_export($column['Column_name'], true);
        }
        if (count($primaryKey) === 1)
        {
            $primaryKey = $primaryKey[0];
        }
        else
        {
            $primaryKey = '[' . implode(', ', $primaryKey) . ']';
        }

        $columns = '';
        foreach ($tableColDefinition as $colDefinition)
        {
            $fieldData = [];
            [$type, $len, $allowedValues] = $this->parseSqlType($colDefinition['Type']);
            $fieldData['type'] = $type;
            if ($len)
            {
                $fieldData['maxLength'] = $len;
            }
            if (stripos($colDefinition['Extra'], 'auto_increment') !== false)
            {
                $fieldData['autoIncrement'] = var_export(true, true);
            }

            if ($allowedValues)
            {
                $fieldData['allowedValues'] = $allowedValues;
            }
            if (isset($fieldData['Null']) && $fieldData['Null'] !== 'NO')
            {
                $fieldData['nullable'] = var_export(true, true);
            }
            if (isset($colDefinition['Default']) && ($colDefinition['Default'] !== null || !empty($fieldData['nullable'])))
            {
                $default = $colDefinition['Default'];
                switch($type)
                {
                    case 'self::INT':
                    case 'self::UINT':
                        if (\is_scalar($default))
                        {
                            $default = (int) $default;
                        }
                        break;
                    case 'self::FLOAT':
                        if (\is_scalar($default))
                        {
                            $default = strval(floatval($default)) + 0;
                        }
                        break;
                    case 'self::BOOL':
                        $default = (int) $default ? true : false;
                        break;
                }

                $fieldData['default'] = var_export($default, true);
            }

            if (!\array_key_exists('default', $fieldData) && empty($fieldData['nullable']))
            {
                $fieldData['required'] = var_export(true, true);
            }

            $definition = [];
            foreach ($fieldData as $key => $value)
            {
                $definition[] = var_export($key, true) . ' => ' . $value;
            }
            $definition = implode($definition, ', ');

            $columns .= '            ' . var_export($colDefinition['Field'], true) . ' => [' . $definition . "],\n";
        }


        $template = <<<TEMPLATE
<?php

namespace {$namespace}\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class {$name} extends Entity
{
    /**
     * @param Structure \$structure
     * @return Structure
     */
    public static function getStructure(Structure \$structure)
    {
        \$structure->table = '{$table}';
        \$structure->shortName = '{$namespace}:{$name}';
        \$structure->primaryKey = {$primaryKey};
        \$structure->columns = [
{$columns}        ];

        return \$structure;
    }
}
TEMPLATE;
        $entityName = "{$namespace}:{$name}";
        $entityName = str_replace('/', '\\', $entityName);

        $output->writeln("Writing entity for {$entityName}");
echo $template."\n\n";
        File::writeFile($filename, $template, false);

        $this->runSubAction($output, [
            'xf-dev:entity-class-properties',
            $entityName
        ]);

        $this->runSubAction($output, [
            'tdt-addon:generate-schema-entity',
            $entityName
        ]);

        return 0;
    }

    /**
     * @param string $sqlType
     * @return array
     */
    protected function parseSqlType($sqlType) : array
    {
        $len = $allowedValues = null;
        if (\preg_match('#^([^\(]+)\s*(?:\(([^\)]+)\)){0,1}\s*(unsigned){0,1}$#i', $sqlType, $matches))
        {
            $proposedType = \utf8_strtolower($matches[1]);
            $proposedLen = empty($matches[2]) ? null : (int) $matches[2];
            $isUnsigned = !empty($matches[3]);
            switch ($proposedType)
            {
                case 'double precision':
                case 'dec':
                case 'fixed':
                case 'numeric':
                case 'real':
                case 'float':
                case 'double':
                    $type = 'self::FLOAT';
                    break;
                case 'char':
                    $type = 'self::STR';
                    $len = 1;
                    break;
                case 'longblob':
                    $type = 'self::BINARY';
                    $len = 4294967295;
                    break;
                case 'mediumblob':
                    $type = 'self::BINARY';
                    $len = 16777215;
                    break;
                case 'blob':
                    $type = 'self::BINARY';
                    $len = 65535;
                    break;
                case 'varbinary':
                    $type = 'self::BINARY';
                    $len = $proposedLen ?: null;
                    break;
                case 'longtext':
                    $type = 'self::STR';
                    $len = 4294967295;
                    break;
                case 'mediumtext':
                    $type = 'self::STR';
                    $len = 16777215;
                    break;
                case 'text':
                    $type = 'self::STR';
                    $len = 65535;
                    break;
                case 'varchar':
                    $type = 'self::STR';
                    $len = $proposedLen ?: null;
                    break;
                case 'bool':
                case 'boolean':
                    $type = 'self::BOOL';
                    break;
                case 'int':
                case 'bigint':
                case 'shortint':
                case 'tinyint':
                    if ($proposedLen === 1)
                    {
                        $type = 'self::BOOL';
                    }
                    else
                    {
                        $type = $isUnsigned ? 'self::UINT' : 'self::INT';
                        if ($proposedType === 'tinyint')
                        {
                            $len = $isUnsigned ? 255 : 128;
                        }
                        else if ($proposedType === 'shortint')
                        {
                            $len = $isUnsigned ? 65536 : 32768;
                        }
                    }
                    break;
                //case 'ENUM':
                //case 'SET':
                default:
                    throw new \RuntimeException("Unknown SQL type: {$sqlType}");
            }
        }
        else
        {
            throw new \RuntimeException("Unknown SQL type: {$sqlType}");
        }

        return [$type, $len, $allowedValues];
    }

    /**
     * @param OutputInterface $output
     * @param array           $args
     */
    public function runSubAction(OutputInterface $output, array $args = []) : void
    {
        $execFinder = new PhpExecutableFinder();

        $builderOptions = [
            $execFinder->find(false),
            \XF::getRootDirectory() . DIRECTORY_SEPARATOR . 'cmd.php',
            '-n'
        ];
        $builderOptions = array_merge($builderOptions, $args);

        if ($verbosityOption = $this->getVerbosityOption($output->getVerbosity()))
        {
            $builderOptions[] = $verbosityOption;
        }

        $builder = ProcessBuilder::create($builderOptions);
        $builder->setTimeout(null);
        $process = $builder->getProcess();

        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelper('process');

        try
        {
            $processHelper->mustRun($output, $process, null, function ($type, $data) use ($output) {
                if ($type === Process::OUT)
                {
                    $output->write($data);
                }
                // Note that progress bar output is in Process::ERR/stderr, but they get streamed to this callback
                // interleaved, so displaying both is difficult. Thus, we need to only display stuff sent stdout.
            });
        }
        catch (ProcessFailedException $e)
        {
            $process = $e->getProcess();
            if ($process->getExitCode() === 222)
            {
                // This indicates that the sub-process threw an exception. It will have been printed and logged
                // so don't trigger the normal exception handling. However, we can't continue so exit.
                exit(1);
            }
        }
    }

    /**
     * @param $key
     * @return \Closure
     */
    protected function getAddOnQuestionFieldValidator($key) : callable
    {
        return function ($value) use ($key) {
            $addOn = \XF::em()->create('XF:AddOn');

            $valid = $addOn->set($key, $value);
            if (!$valid)
            {
                $errors = $addOn->getErrors();
                if (isset($errors[$key]))
                {
                    throw new \InvalidArgumentException($errors[$key]);
                }
            }

            return $value;
        };
    }
}
