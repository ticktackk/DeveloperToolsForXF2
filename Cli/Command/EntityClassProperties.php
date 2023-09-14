<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\App as BaseApp;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Entity;
use XF\Cli\Command\Development\RequiresDevModeTrait;
use XF\Mvc\Entity\Repository;
use XF\Util\File as FileUtil;
use XF\Util\Php as PhpUtil;
use XF\Mvc\Entity\Structure as EntityStructure;
use XF\Entity\ClassExtension as ClassExtensionEntity;
use XF\Repository\ClassExtension as ClassExtensionRepo;
use XF\AddOn\Manager as AddOnManager;

use function is_array, is_string, strlen;

/**
 * @since 1.4.0
 */
class EntityClassProperties extends Command
{
    use RequiresDevModeTrait, ClassPropertiesCommandTrait;

    protected function configure() : void
    {
        $this
            ->setName('tck-devtools:entity-class-properties')
            ->setAliases(['tck-dt:entity-class-properties'])
            ->setDescription('Applies class properties to type hint columns, relations and getters')
            ->addArgument(
                'addon-or-entity',
                InputArgument::REQUIRED,
                'Add-on ID or specific Entity short name to generate Entity class properties for. Note: Existing class properties will be overwritten.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $entities = [];

        $addOnOrEntity = $input->getArgument('addon-or-entity');
        if (strpos($addOnOrEntity, ':') !== false)
        {
            $entities[] = $addOnOrEntity;
            $addOnId = str_replace('\\', '/', explode(':', $addOnOrEntity, 2)[0]);
            $addOnJson = null;

            if ($addOnId !== 'XF')
            {
                $manager = $this->addOnManager();
                $addOn = $manager->getById($addOnId);
                $addOnJson = $addOn->getJson();
            }
        }
        else
        {
            if ($addOnOrEntity === 'XF')
            {
                $addOnId = 'XF';
                $addOnJson = null;
                $path = \XF::getSourceDirectory() . \XF::$DS . 'XF' . \XF::$DS . 'Entity';
            }
            else
            {
                $manager = $this->addOnManager();
                $addOn = $manager->getById($addOnOrEntity);
                if (!$addOn || !$addOn->isAvailable())
                {
                    $output->writeln('Add-on could not be found.');
                    return 1;
                }

                $addOnId = $addOn->getAddOnId();
                $addOnJson = $addOn->getJson();
                $path = $manager->getAddOnPath($addOnId) . \XF::$DS . 'Entity';
            }

            if (!file_exists($path) || !is_dir($path))
            {
                $output->writeln('<error>The selected add-on does not appear to have an Entity directory.</error>');
                return 1;
            }

            $iterator = new \RegexIterator(
                FileUtil::getRecursiveDirectoryIterator($path, null, null), '/\.php$/'
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator AS $name => $file)
            {
                $name = str_replace('.php', '', $file->getFilename());
                $subDir = substr($file->getPath(), strlen($path));
                $subDir = ltrim(str_replace('/', '\\', $subDir) . '\\', '\\');
                $entities[] = str_replace('/', '\\', $addOnId) . ':' . $subDir . $name;
            }
        }

        if (!$entities)
        {
            $output->writeln('<error>No entity classes could be found.</error>');
            return 1;
        }

        $requireAddOnIds = null;
        $softRequireAddOnIds = null;
        if (is_array($addOnJson))
        {
            $requireAddOnIds = array_keys($addOnJson['require']);
            if (isset($addOnJson['require-soft']))
            {
                $softRequireAddOnIds = array_keys($addOnJson['require-require']);
            }
        }

        foreach ($entities AS $entity)
        {
            $class = \XF::stringToClass($entity, '%s\Entity\%s');

            $reflection = new \ReflectionClass($class);
            if (!$reflection->isInstantiable() || !$reflection->isSubclassOf('XF\Mvc\Entity\Entity'))
            {
                continue;
            }

            $structure = $class::getStructure(new EntityStructure());

            $path = realpath(\XF::$autoLoader->findFile($class));
            $contents = file_get_contents($path);

            $output->writeln("Writing class properties for entity $entity");

            $docPlaceholder = $this->getDocPlaceholder();
            $existingComment = $reflection->getDocComment();

            if (!$existingComment)
            {
                $search = 'class ' . $reflection->getShortName() . ' extends ';
                $replace = "$docPlaceholder\n$search";
                $newContents = str_replace($search, $replace, $contents);
            }
            else
            {
                $newContents = str_replace($existingComment, $docPlaceholder, $contents);
            }

            $typeMap = $this->getEntityTypeMap();
            $listMap = $this->getListTypeMap();

            $getters = [];
            foreach ($structure->getters AS $getter => $def)
            {
                if (is_array($def) && isset($def['getter']) && is_string($def['getter']))
                {
                    $methodName = $def['getter'];
                }
                else
                {
                    $methodName = 'get' . ucfirst(PhpUtil::camelCase($getter));
                }
                if (!$reflection->hasMethod($methodName))
                {
                    continue;
                }
                $method = $reflection->getMethod($methodName);

                $comment = $method->getDocComment();
                $returnType = $method->getReturnType();
                if ($comment && preg_match('/^\s*?\*\s*?@return\s+(\S+)/mi', $comment, $matches))
                {
                    $type = $matches[1];
                }
                else if ($returnType)
                {
                    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                    if ($returnType instanceof \ReflectionUnionType)
                    {
                        $returnTypes = $returnType->getTypes();
                    }
                    else
                    {
                        $returnTypes = [$returnType];
                    }

                    $types = [];
                    $nullable = false;
                    foreach ($returnTypes AS $returnType)
                    {
                        if ($returnType->getName() === 'null')
                        {
                            $nullable = true;
                        }
                        else
                        {
                            $types[] = ($returnType->isBuiltin() ? '' : '\\')
                                . $returnType->getName();

                            if ($returnType->allowsNull())
                            {
                                $nullable = true;
                            }
                        }
                    }

                    $type = implode('|', $types);
                    if ($nullable)
                    {
                        if (strlen($type))
                        {
                            $type .= '|';
                        }

                        $type .= 'null';
                    }
                }
                else
                {
                    $type = null;
                }

                if (is_string($type))
                {
                    $newType = [];
                    $typeParts = explode('|', $type);

                    foreach ($typeParts AS $typePart)
                    {
                        if (substr($typePart, 0, 1) !== '\\')
                        {
                            $newType[] = $typePart;
                            continue;
                        }

                        $typePart = substr($typePart, 1);
                        $isMulti = substr($typePart, strlen($typePart) - 2) === '[]';
                        if ($isMulti)
                        {
                            $typePart = substr($typePart, 0, strlen($typePart) - 2);
                        }

                        $typeHintClasses = $this->getTypeHintForClass(
                            $typePart,
                            $addOnId,
                            $requireAddOnIds,
                            $softRequireAddOnIds
                        );
                        foreach ($typeHintClasses AS $typeHintClass)
                        {
                            $newType[] = '\\' . $typeHintClass . ($isMulti ? '[]' : '');
                        }
                    }
                    $newType = array_unique($newType);

                    $type = implode('|', $newType);
                }

                $getters[$getter] = [
                    'type' => $type ? (is_array($type) ? $type : trim($type)) : 'mixed',
                    'readOnly' => true
                ];
            }

            $columns = [];
            foreach ($structure->columns AS $column => $def)
            {
                if (isset($getters[$column]))
                {
                    $getters[$column]['readOnly'] = false;

                    // There's an overlapping getter so this column
                    // is only accessible via the bypass suffix.
                    $column .= '_';
                }
                $columns[$column] = [
                    'readOnly' => !empty($def['readOnly']),
                    'type' => !empty($def['typeHint']) ? $def['typeHint'] : $typeMap[$def['type']],
                    'null' => !empty($def['nullable'])
                ];

                if (array_key_exists('list', $def)
                    && array_key_exists('type', $def['list'])
                    && isset($listMap[$def['list']['type']])
                )
                {
                    $columns[$column]['type'] = 'array|' . $listMap[$def['list']['type']] . '[]';
                }
                else if ($def['type'] === Entity::STR && array_key_exists('censor', $def))
                {
                    $columns[$column . '_'] = $columns[$column];
                }
            }

            $relations = [];
            foreach ($structure->relations AS $relation => $def)
            {
                if (isset($getters[$relation]))
                {
                    // There's an overlapping getter so this relation
                    // is only accessible via the bypass suffix.
                    $relation .= '_';
                }

                $relationEntityClasses = $this->getTypeHintForClass(
                    \XF::stringToClass($def['entity'], '%s\Entity\%s'),
                    $addOnId,
                    $requireAddOnIds,
                    $softRequireAddOnIds,
                );

                $relations[$relation] = [
                    'type' => $relationEntityClasses,
                    'many' => ($def['type'] === Entity::TO_MANY),
                    'readOnly' => true
                ];
            }

            $newComment = '/**' . "\n";

            if ($columns)
            {
                $newComment .= ' * COLUMNS';
                foreach ($columns AS $column => $type)
                {
                    $newComment .= "\n" . ' * @property' . ($type['readOnly'] ? '-read' : '') . ' ' . $type['type'] . ($type['null'] ? '|null' : '') . ' $' . $column;
                }
            }

            if ($relations)
            {
                if ($columns)
                {
                    $newComment .= "\n *\n";
                }
                $newComment .= ' * RELATIONS';
                foreach ($relations AS $relation => $type)
                {
                    $typeProp = array_map(function (string $type)
                    {
                        return ltrim($type, '\\');
                    }, $type['type']);
                    $typeProp = '\\' . implode(($type['many'] ? '[]' : '') . '|\\', $typeProp);

                    if ($type['many'])
                    {
                        $typeProp = '\XF\Mvc\Entity\AbstractCollection|' . $typeProp . '[]';
                    }

                    $newComment .= "\n" . ' * @property' . ($type['readOnly'] ? '-read' : '') . ' ' . $typeProp . ' $' . $relation;
                }
            }

            if ($getters)
            {
                if ($columns || $relations)
                {
                    $newComment .= "\n *\n";
                }
                $newComment .= ' * GETTERS';
                foreach ($getters AS $getter => $type)
                {
                    if (is_array($type['type']))
                    {
                        $type['type'] = array_map(function (string $type)
                        {
                            return ltrim($type, '\\');
                        }, $type['type']);
                        $type['type'] = '\\' . implode('|\\', $type['type']);
                    }

                    $newComment .= "\n" . ' * @property' . ($type['readOnly'] ? '-read' : '') . ' ' . $type['type'] . ' $' . $getter;
                }
            }

            $newComment .= "\n */";

            $newContents = str_replace($docPlaceholder, $newComment, $newContents);

            if (FileUtil::writeFile($path,$newContents, false))
            {
                $output->writeln("Written out class properties for entity $entity");
            }
            else
            {
                $output->writeln("Could not write out class properties for entity $entity");
            }
            $output->writeln("");
        }

        $output->writeln("Done!");
        return 0;
    }

    protected function getDocPlaceholder() : string
    {
        return '/** <TCK:DT:DOC_COMMENT> */';
    }

    protected function getEntityTypeMap() : array
    {
        /** @noinspection PhpDeprecationInspection */
        return [
            Entity::INT => 'int',
            Entity::UINT => 'int',
            Entity::FLOAT => 'float',
            Entity::BOOL => 'bool',
            Entity::STR => 'string',
            Entity::BINARY => 'string',
            Entity::SERIALIZED => 'array|bool', // try to decode but bool on failure
            Entity::SERIALIZED_ARRAY => 'array',
            Entity::JSON => 'array|null', // try to decode but null on failure
            Entity::JSON_ARRAY => 'array',
            Entity::LIST_LINES => 'array',
            Entity::LIST_COMMA => 'array',
            Entity::LIST_ARRAY => 'array'
        ];
    }

    protected function getListTypeMap() : array
    {
        return [
            'int' => 'int',
            'uint' => 'int',
            'posint' => 'int',
            'str' => 'string'
        ];
    }

    protected function getAllClassesExtendingClass(
        string $addOnId,
        ?array $requireAddOnIds,
        ?array $softRequireAddOnIds,
        string $class
    ) : array
    {
        $addOnIds = [$addOnId];
        $classes = [$class];

        if (is_array($requireAddOnIds))
        {
            array_push($addOnIds, ...$requireAddOnIds);
        }

        if (is_array($softRequireAddOnIds))
        {
            array_push($addOnIds, ...$softRequireAddOnIds);
        }

        /** @var AbstractCollection|ClassExtensionEntity[] $classExtensions */
        $classExtensions = $this->getClassExtensionRepo()->findExtensionsForList()
            ->where('from_class', '=', $class)
            ->where('addon_id', '=', $addOnIds)
            ->fetch();
        foreach ($classExtensions AS $classExtension)
        {
            $classes[] = $classExtension->to_class;
        }

        return array_unique($classes);
    }

    /**
     * @return Repository|ClassExtensionRepo
     */
    protected function getClassExtensionRepo() : ClassExtensionRepo
    {
        return $this->repository('XF:ClassExtension');
    }

    protected function app() : BaseApp
    {
        return \XF::app();
    }

    protected function addOnManager() : AddOnManager
    {
        return $this->app()->addOnManager();
    }

    protected function repository(string $class) : Repository
    {
        return $this->app()->repository($class);
    }
}