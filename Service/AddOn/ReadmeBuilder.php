<?php

namespace TickTackk\DeveloperTools\Service\AddOn;

use Jawira\CaseConverter\Convert;
use League\HTMLToMarkdown\Environment;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Console\Command\Command;
use XF\AddOn\AddOn;
use XF\App as BaseApp;
use XF\Entity\AdminPermission as AdminPermissionEntity;
use XF\Entity\AdvertisingPosition as AdvertisingPositionEntity;
use XF\Entity\ApiScope as ApiScopeEntity;
use XF\Entity\BbCode as BbCodeEntity;
use XF\Entity\BbCodeMediaSite as BbCodeMediaSiteEntity;
use XF\Entity\CronEntry as CronEntryEntity;
use XF\Entity\Option as OptionEntity;
use XF\Entity\OptionGroupRelation as OptionGroupRelationEntity;
use XF\Entity\Permission as PermissionEntity;
use XF\Entity\Phrase as PhraseEntity;
use XF\Entity\StyleProperty as StylePropertyEntity;
use XF\Entity\WidgetDefinition as WidgetDefinitionEntity;
use XF\Entity\WidgetPosition as WidgetPositionEntity;
use XF\Html\Renderer\BbCode;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\Repository\AddOn as AddonRepo;
use XF\Service\AbstractService;
use XF\Service\ValidateAndSavableTrait;
use XF\Util\File as FileUtil;
use XF\Util\Php as PhpUtil;
use xprt64\HtmlTableToMarkdownConverter\TableConverter;

/**
 * Class ReadmeBuilder
 *
 * @package TickTackk\DeveloperTools\Service\AddOn
 */
class ReadmeBuilder extends AbstractService
{
    use ValidateAndSavableTrait;

    public const OUTPUT_FORMAT_MARKDOWN = 'md';

    public const OUTPUT_FORMAT_HTML = 'html';

    public const OUTPUT_FORMAT_BB_CODE = 'bb_code';

    /**
     * @var AddOn
     */
    protected $addOn;

    /**
     * @var array
     */
    protected $types;

    /**
     * @var bool
     */
    protected $copy;


    /**
     * ReadMeGenerator constructor.
     *
     * @param BaseApp $app
     * @param AddOn $addOn
     * @param array $types
     * @param bool $copy
     */
    public function __construct(BaseApp $app, AddOn $addOn, array $types = [], bool $copy = false)
    {
        parent::__construct($app);

        $this->addOn = $addOn;
        $this->types = $types;
        $this->copy = $copy;
    }

    /**
     * @return AddOn
     */
    protected function getAddOn() : AddOn
    {
        return $this->addOn;
    }

    /**
     * @param AddOn|null $addOn
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function getData(AddOn $addOn = null) : array
    {
        $addOn = $addOn ?: $this->getAddOn();
        $installedAddOn = $addOn->getInstalledAddOn();
        $addOnJson = $addOn->getJson();

        $data = [
            'title' => $installedAddOn->title,
            'description' => $addOnJson['description'] ?? '',
            'requirements' => $addOnJson['require'] ?? []
        ];

        $finderAndDataMap = $this->getFinderAndDataMap();
        foreach ($finderAndDataMap AS $identifier => $dataKey)
        {
            $finder = $this->finder($identifier);
            $this->applyAddOnIdCondition($finder);
            $this->applyDefaultOrder($finder);
            $data[$dataKey] = $finder->fetch();
        }

        $data['cli_commands'] = $this->getCliCommands();

        return $data;
    }

    /**
     * @param string $hook
     * @param string $contents
     * @param string $readme
     *
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    protected function handleHookContents(string $hook, string $contents, string &$readme) : void
    {
        $converter = new Convert($hook);
        $hook = $converter->toMacro();

        $prependToReadme = function (string $fullHook) use(&$readme)
        {
            $addOn = $this->getAddOn();

            $docsDir = FileUtil::canonicalizePath("_dev", $addOn->getAddOnDirectory());
            $readmeHookDir = FileUtil::canonicalizePath("README_HOOK", $docsDir);
            $hookFilePath = FileUtil::canonicalizePath("{$fullHook}.html", $readmeHookDir);

            if (\file_exists($hookFilePath) && \is_readable($hookFilePath))
            {
                $readme .= \utf8_trim(\file_get_contents($hookFilePath));
            }
        };

        $prependToReadme('BEFORE_' . $hook);
        $readme .= $contents;
        $prependToReadme('AFTER_' . $hook);
    }

    /**
     * @return array<string, string>
     */
    protected function getFinderAndDataMap() : array
    {
        return [
            'XF:BbCode' => 'bb_codes',
            'XF:BbCodeMediaSite' => 'bb_code_media_sites',
            'XF:CronEntry' => 'cron_entries',
            'XF:Option' => 'options',
            'XF:Permission' => 'permissions',
            'XF:StyleProperty' => 'style_properties',
            'XF:WidgetDefinition' => 'widget_definitions',
            'XF:WidgetPosition' => 'widget_positions',
            'XF:AdminPermission' => 'admin_permissions',
            'XF:AdvertisingPosition' => 'advertising_positions',
            'XF:ApiScope' => 'api_scopes'
        ];
    }

    /**
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function getCliCommands() : array
    {
        $addOn = $this->getAddOn();

        $addOnId = $addOn->getAddOnId();
        $addOnDir = $addOn->getAddOnDirectory();

        $commandsPath = FileUtil::canonicalizePath('Command', FileUtil::canonicalizePath('Cli', $addOnDir));
        if (!\file_exists($commandsPath) || !\is_dir($commandsPath))
        {
            return [];
        }

        $iterator = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($commandsPath),
            function(\SplFileInfo $entry, /** @noinspection PhpUnusedParameterInspection */$null, \RecursiveIterator $iterator)
            {
                if ($iterator->hasChildren())
                {
                    return true;
                }

                return ($entry->isFile() && $entry->getExtension() === 'php');
            }
        );

        $baseClass = '\\' . \str_replace('/', '\\', $addOnId) . '\Cli\Command';
        $cliCommands = [];
        foreach (new \RecursiveIteratorIterator($iterator) AS $file)
        {
            /** @var \DirectoryIterator $file */
            $localPath = \str_replace($commandsPath, '', $file->getPathname());
            $localPath = \trim(str_replace('\\', '/', $localPath), '/');

            $className = $baseClass . '\\' . str_replace('/', '\\', $localPath);
            $className = preg_replace('/\.php$/', '', $className);

            if (!\class_exists($className))
            {
                continue;
            }

            $reflection = new \ReflectionClass($className);
            if ($reflection->isInstantiable() && $reflection->isSubclassOf(Command::class))
            {
                /** @var Command $command */
                $command = new $className();
                $definition = $command->getDefinition();

                $cliCommands[$command->getName()] = [
                    'name' => $command->getName(),
                    'aliases' => $command->getAliases(),
                    'description' => $command->getDescription(),
                    'arguments' => [],
                    'options' => []
                ];

                foreach ($definition->getArguments() AS $argument)
                {
                    $cliCommands[$command->getName()]['arguments'][$argument->getName()] = [
                        'name' => $argument->getName(),
                        'description' => $argument->getDescription(),
                        'default' => $argument->getDefault(),
                        'required' => $argument->isRequired()
                    ];
                }

                foreach ($definition->getOptions() AS $option)
                {
                    $cliCommands[$command->getName()]['options'][$option->getName()] = [
                        'name' => $option->getName(),
                        'description' => $option->getDescription(),
                        'default' => $option->getDefault(),
                        'shortcut' => $option->getShortcut()
                    ];
                }
            }
        }

        return $cliCommands;
    }

    /**
     * @return Repository|AddonRepo
     */
    protected function getAddonRepo() : AddonRepo
    {
        return $this->repository('XF:AddOn');
    }

    /**
     * @param Finder $finder
     *
     * @return Finder
     */
    protected function applyAddOnIdCondition(Finder $finder) : Finder
    {
        $addOn = $this->getAddOn();
        $finder->where('addon_id', $addOn->getAddOnId());

        if (\array_key_exists('display_order', $finder->getStructure()->columns))
        {
            $finder->setDefaultOrder('display_order', 'ASC');
        }

        return $finder;
    }

    /**
     * @param Finder $finder
     *
     * @return Finder
     */
    protected function applyDefaultOrder(Finder $finder) : Finder
    {
        $structure = $finder->getStructure();
        $columns = $structure->columns;

        if (\array_key_exists('display_order', $columns))
        {
            $finder->setDefaultOrder(
                (\array_key_exists('lft', $columns) && \array_key_exists('rgt', $columns)) ?
                'lft' : 'display_order'
            );
        }

        return $finder;
    }

    /**
     * @return array
     */
    protected function _validate() : array
    {
        $errors = [];

        $addOn = $this->getAddOn();

        $addOnJson = $addOn->getJson();
        $installedAddOn = $addOn->getInstalledAddOn();
        if (!$installedAddOn) // add support for reading from _output directory maybe later?
        {
            $errors[] = \XF::phrase('tckDeveloperTools_x_addon_must_be_installed_to_generate_readme', [
                'title' => $addOnJson['title']
            ]);
        }

        return $errors;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    protected function generateHtml() : string
    {
        $data = $this->getData();
        $readme  = '';

        $titleBlock = "<h1>{$data['title']}";
        $requirements = $data['requirements'];
        if (\count($requirements))
        {
            $addOnRepo = $this->getAddonRepo();
            if (\array_key_exists('XF', $requirements))
            {
                $versionString = $addOnRepo->inferVersionStringFromId(\reset($requirements['XF']));
                $titleBlock .= " for XenForo {$versionString}+";
                unset($requirements['XF']);
            }
        }
        $titleBlock .= '</h1>';
        $this->handleHookContents('Title', $titleBlock, $readme);

        $descriptionBlock = '';
        if ($data['description'])
        {
            $descriptionBlock .= '<h2>Description</h2>';
            $descriptionBlock .= "<p>{$data['description']}</p>";
        }
        $this->handleHookContents('Description', $descriptionBlock, $readme);

        $requirementsBlock = '';
        if (\count($requirements))
        {
            $requirementsBlock .= '<h2>Requirements</h2><ul>';
            foreach ($requirements AS $requirement => $version)
            {
                $readableRequirement = ($version === '*') ? $requirement : \end($version);
                $requirementsBlock .= "<li>{$readableRequirement}</li>";
            }
            $requirementsBlock .= '</ul>';
        }
        $this->handleHookContents('Requirements', $requirementsBlock, $readme);

        /**
         * @param string $tableTitle
         * @param ArrayCollection|array|Entity[] $entities
         * @param array $headerMap
         */
        $generateTableFromEntity = function (string $tableTitle, $entities, array $headerMap) use(&$readme) : void
        {
            $block = '';

            if (\count($entities))
            {
                $block .= "<h2>{$tableTitle}</h2>";
                $block .= '<table style="width:100%">';

                // header row
                $block .= '<thead><tr>';
                foreach (\array_keys($headerMap) AS $header)
                {
                    $block .= "<th>{$header}</th>";
                }
                $block .= '</tr></thead><tbody>';

                // entity row
                foreach ($entities AS $entity)
                {
                    $block .= '<tr>';
                    foreach ($headerMap AS $header => $getter)
                    {
                        if ($getter instanceof \Closure)
                        {
                            $value = $getter($entity);
                        }
                        else
                        {
                            $value = $entity[$getter];
                        }

                        $block .= "<td>{$value}</td>";
                    }
                    $block .= '</tr>';
                }

                $block .= '</tbody></table>';
            }

            $this->handleHookContents($tableTitle, $block, $readme);
        };

        $generateTableFromEntity('Options', $data['options'], [
            'Group' => function(OptionEntity $option)
            {
                /** @var OptionGroupRelationEntity $optionGroupRelation */
                $optionGroupRelation = $option->Relations->first();
                if (!$optionGroupRelation)
                {
                    return 'Unknown';
                }

                $optionGroup = $optionGroupRelation->OptionGroup;
                if (!$optionGroup)
                {
                    return 'Unknown';
                }

                $group = $optionGroup->getMasterPhrase('title')->phrase_text;
                if ($optionGroup->debug_only)
                {
                    $group .= ' (Debug only)';
                }

                return $group;
            },
            'Name' => function(OptionEntity $option)
            {
                return $option->getMasterPhrase(true)->phrase_text;
            },
            'Description' => function(OptionEntity $option)
            {
                return $option->getMasterPhrase(false)->phrase_text;
            }
        ]);

        $generateTableFromEntity('Permissions', $data['permissions'], [
            'Group' => function(PermissionEntity $permission)
            {
                return $permission->Interface->getMasterPhrase()->phrase_text;
            },
            'Permission' => function(PermissionEntity $permission)
            {
                return $permission->getMasterPhrase()->phrase_text;
            }
        ]);

        $generateTableFromEntity('Admin Permissions', $data['admin_permissions'], [
            'Permission' => function(AdminPermissionEntity $adminPermission)
            {
                return $adminPermission->getMasterPhrase()->phrase_text;
            }
        ]);

        $generateTableFromEntity('BB Codes', $data['bb_codes'], [
            'Name' => function(BbCodeEntity $bbCode)
            {
                /** @var PhraseEntity $titlePhrase */
                $titlePhrase = $bbCode->getRelationOrDefault('MasterTitle');
                return $titlePhrase->phrase_text;
            },
            'Tag' => function(BbCodeEntity $bbCode)
            {
                $tag = \strtoupper($bbCode->bb_code_id);
                return "<code>{$tag}</code>";
            },
            'Description' => function(BbCodeEntity $bbCode)
            {
                /** @var PhraseEntity $descPhrase */
                $descPhrase = $bbCode->getRelationOrDefault('MasterDesc');
                return $descPhrase->phrase_text;
            },
            'Example' => function(BbCodeEntity $bbCode)
            {
                /** @var PhraseEntity $examplePhrase */
                $examplePhrase = $bbCode->getRelationOrDefault('MasterExample');
                return $examplePhrase->phrase_text;
            }
        ]);

        $generateTableFromEntity('BB Code Media Sites', $data['bb_code_media_sites'], [
            'Site' => function(BbCodeMediaSiteEntity $bbCodeMediaSite)
            {
                return $bbCodeMediaSite->site_title;
            },
            'Url' => function(BbCodeMediaSiteEntity $bbCodeMediaSite)
            {
                return "<code>{$bbCodeMediaSite->site_url}</code>";
            },
            'oEmbed' => function(BbCodeMediaSiteEntity $bbCodeMediaSite)
            {
                return $bbCodeMediaSite->oembed_enabled ? 'Yes' : 'No';
            }
        ]);

        $generateTableFromEntity('Style Properties', $data['style_properties'], [
            'Group' => function(StylePropertyEntity $styleProperty)
            {
                $stylePropertyGroup = $styleProperty->Group;
                if (!$stylePropertyGroup)
                {
                    return 'Unknown';
                }

                return $stylePropertyGroup->getMasterPhrase(true)->phrase_text;
            },
            'Property' => function(StylePropertyEntity $styleProperty)
            {
                return $styleProperty->getMasterPhrase(true)->phrase_text;
            },
            'Description' => function(StylePropertyEntity $styleProperty)
            {
                return $styleProperty->getMasterPhrase(false)->phrase_text;
            }
        ]);

        $generateTableFromEntity('Advertising Positions', $data['advertising_positions'], [
            'Position' => function(AdvertisingPositionEntity $advertisingPosition)
            {
                return $advertisingPosition->MasterTitle->phrase_text . " (<code>{$advertisingPosition->position_id}</code>)";
            },
            'Description' => function(AdvertisingPositionEntity $advertisingPosition)
            {
                return $advertisingPosition->MasterDescription->phrase_text;
            },
            'Arguments' => function(AdvertisingPositionEntity $advertisingPosition)
            {
                $positionArguments = $advertisingPosition->arguments;
                if (!\count($positionArguments))
                {
                    return '';
                }

                $positionArgumentsHtml = '<ul>';
                foreach ($positionArguments AS $positionArgument)
                {
                    $positionArgumentsHtml .= "<li>{$positionArgument['argument']}";
                    if ($positionArgument['required'])
                    {
                        $positionArgumentsHtml .= ' (Required)';
                    }
                    $positionArgumentsHtml .= '</li>';
                }
                $positionArgumentsHtml .= '</ul>';

                return $positionArgumentsHtml;
            }
        ]);

        $generateTableFromEntity('Widget Positions', $data['widget_positions'], [
            'Position' => function(WidgetPositionEntity $widgetPosition)
            {
                return $widgetPosition->getMasterTitlePhrase()->phrase_text . " (<code>{$widgetPosition->position_id}</code>)";
            },
            'Description' => function(WidgetPositionEntity $widgetPosition)
            {
                return $widgetPosition->getMasterDescriptionPhrase()->phrase_text;
            }
        ]);

        $generateTableFromEntity('Widget Definitions', $data['widget_definitions'], [
            'Definition' => function(WidgetDefinitionEntity $widgetDefinition)
            {
                return $widgetDefinition->getMasterTitlePhrase()->phrase_text . " (<code>{$widgetDefinition->definition_id}</code>)";
            },
            'Description' => function(WidgetDefinitionEntity $widgetDefinition)
            {
                return $widgetDefinition->getMasterDescriptionPhrase()->phrase_text;
            }
        ]);

        $generateTableFromEntity('Cron Entries', $data['cron_entries'], [
            'Name' => function(CronEntryEntity $cronEntry)
            {
                return $cronEntry->getMasterPhrase()->phrase_text;
            },
            'Run on...' => function(CronEntryEntity $cronEntry)
            {
                $runRules = $cronEntry->run_rules;
                if ($runRules['day_type'] === 'dom')
                {
                    if (\in_array(-1, $runRules['dom'], true))
                    {
                        return 'Any day of the month';
                    }

                    $dayMap = [];
                    $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                    for($i = 1; $i <= 31; $i++)
                    {
                        $dayMap[$i] = $numberFormatter->format($i);
                    }

                    $days = [];
                    foreach ($runRules['dom'] AS $dayOfMonth)
                    {
                        $days[] = $dayMap[$dayOfMonth];
                    }

                    return 'On ' . \implode(', ', $days);
                }

                if (\in_array(-1, $runRules['dow']))
                {
                    return 'Any day of the week';
                }

                $daysMap = [
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday'
                ];

                $days = [];
                foreach ($runRules['dom'] AS $dayOfWeek)
                {
                    $days[] = $daysMap[$dayOfWeek];
                }

                return 'On ' . \implode(', ', $days);
            },
            'Run at hours' => function(CronEntryEntity $cronEntry)
            {
                $hoursMap = [
                    -1 => 'Any'
                ];

                for($i = 0; $i <= 23; $i++)
                {
                    $hoursMap[$i] = \date("gA", \strtotime("{$i}:00:00 UTC"));
                }

                $hours = [];
                $runRules = $cronEntry->run_rules;
                foreach ($runRules['hours'] AS $hour)
                {
                    $hours[] = $hoursMap[$hour];
                }

                return \implode(', ', $hours);
            },
            'Run at minutes' => function(CronEntryEntity $cronEntry)
            {
                $minutesMap = [
                    -1 => 'Any'
                ];

                for($i = 0; $i <= 59; $i++)
                {
                    $minutesMap[$i] = $i;
                }

                $minutes = [];
                $runRules = $cronEntry->run_rules;
                foreach ($runRules['minutes'] AS $minute)
                {
                    $minutes[] = $minute;
                }

                return \implode(', ', $minutes);
            }
        ]);

        $generateTableFromEntity('REST API Scopes', $data['api_scopes'], [
            'Scope' => function(ApiScopeEntity $apiScope)
            {
                return "<code>{$apiScope->api_scope_id}</code>";
            },
            'Description' => function(ApiScopeEntity $apiScope)
            {
                return $apiScope->getMasterPhrase()->phrase_text;
            }
        ]);

        $generateTableFromEntity('CLI Commands', $data['cli_commands'], [
            'Command' => function(array $command)
            {
                return "<code>{$command['name']}</code>";
            },
            'Description' => function(array $command)
            {
                return $command['description'];
            }
        ]);

        return $readme;
    }

    /**
     * @throws \Exception
     */
    protected function _save() : void
    {
        $addOn = $this->getAddOn();
        $addOnRoot = $addOn->getAddOnDirectory();

        $fileAndOutputFormatMap = [];
        foreach ($this->types as $type)
        {
            if ($type === static::OUTPUT_FORMAT_MARKDOWN)
            {
                $fileAndOutputFormatMap[static::OUTPUT_FORMAT_MARKDOWN] = FileUtil::canonicalizePath(
                    'README.md',
                    $addOnRoot
                );
            }
            else if ($type === static::OUTPUT_FORMAT_BB_CODE)
            {
                $fileAndOutputFormatMap[static::OUTPUT_FORMAT_BB_CODE] = FileUtil::canonicalizePath(
                    '_dev/resource_description.txt',
                    $addOnRoot
                );
            }
            else if ($type === static::OUTPUT_FORMAT_HTML)
            {
                $fileAndOutputFormatMap[static::OUTPUT_FORMAT_HTML] = FileUtil::canonicalizePath(
                    '_dev/resource_description.html',
                    $addOnRoot
                );
            }
        }

        $readmeHtml = $this->generateHtml();
        foreach ($fileAndOutputFormatMap AS $format => $filePath)
        {
            $contents = $readmeHtml;
            if ($format === static::OUTPUT_FORMAT_MARKDOWN)
            {
                $environment = Environment::createDefaultEnvironment([]);
                $environment->addConverter(new TableConverter());

                $htmlConverter = new HtmlConverter($environment);
                $htmlConverter->getConfig()->setOption('suppress_errors', true);
                $contents = $htmlConverter->convert($readmeHtml);
            }
            else if ($format === static::OUTPUT_FORMAT_BB_CODE)
            {
                $contents = BbCode::renderFromHtml($readmeHtml, [
                    'handleCodeTagForTckDeveloperTools' => true
                ]);
                $contents = \XF::cleanString($contents);
                $contents = \preg_replace_callback('#(^\[SIZE=\d].*?\[/SIZE]\n)#sm', function ($header)
                {
                    $header = \utf8_trim($header[0]);
                    return "\n{$header}";
                }, $contents);
            }

            FileUtil::writeFile($filePath, utf8_trim($contents), false);

            if ($this->copy)
            {
                FileUtil::copyFile(
                    $filePath,
                    FileUtil::canonicalizePath(
                        '_no_upload/' . pathinfo($filePath, PATHINFO_BASENAME),
                        $addOnRoot
                    )
                );
            }
        }
    }
}