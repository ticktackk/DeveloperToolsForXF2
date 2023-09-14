<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\App as BaseApp;
use XF\Cli\Command\Development\RequiresDevModeTrait;
use XF\Mvc\Entity\Repository;
use XF\Util\File as FileUtil;
use XF\Mvc\Entity\Structure as EntityStructure;
use XF\Repository\ClassExtension as ClassExtensionRepo;
use XF\AddOn\Manager as AddOnManager;

use function is_array, strlen;

/**
 * @since 1.4.4
 */
class FinderClassProperties extends Command
{
    use RequiresDevModeTrait, ClassPropertiesCommandTrait;

    protected function configure() : void
    {
        $this
            ->setName('tck-devtools:finder-class-properties')
            ->setAliases(['tck-dt:finder-class-properties'])
            ->setDescription('Applies class properties to type hint relations.')
            ->addArgument(
                'addon-or-finder',
                InputArgument::REQUIRED,
                'Add-on ID or specific Finder short name to generate Finder class properties for. Note: Existing class properties will be overwritten.'
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
        $finders = [];

        $addOnOrFinder = $input->getArgument('addon-or-finder');
        if (strpos($addOnOrFinder, ':') !== false)
        {
            $finders[] = $addOnOrFinder;
            $addOnId = str_replace('\\', '/', explode(':', $addOnOrFinder, 2)[0]);
            $addOnJson = null;

            if ($addOnId !== 'XF')
            {
                $manager = $this->addOnManager();
                $addOn = $manager->getById($addOnId);
                if (!$addOn || !$addOn->isAvailable())
                {
                    $output->writeln('Add-on could not be found.');
                    return 1;
                }

                $addOnJson = $addOn->getJson();
            }
        }
        else
        {
            if ($addOnOrFinder === 'XF')
            {
                $addOnId = 'XF';
                $addOnJson = null;
                $path = \XF::getSourceDirectory() . \XF::$DS . 'XF' . \XF::$DS . 'Finder';
            }
            else
            {
                $manager = $this->addOnManager();
                $addOn = $manager->getById($addOnOrFinder);
                if (!$addOn || !$addOn->isAvailable())
                {
                    $output->writeln('Add-on could not be found.');
                    return 1;
                }

                $addOnId = $addOn->getAddOnId();
                $addOnJson = $addOn->getJson();
                $path = $manager->getAddOnPath($addOnId) . \XF::$DS . 'Finder';
            }

            if (!file_exists($path) || !is_dir($path))
            {
                $output->writeln('<error>The selected add-on does not appear to have a Finder directory.</error>');
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
                $finders[] = str_replace('/', '\\', $addOnId) . ':' . $subDir . $name;
            }
        }

        if (!$finders)
        {
            $output->writeln('<error>No finder classes could be found.</error>');
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

        foreach ($finders AS $finder)
        {
            $entityClass = \XF::stringToClass($finder, '%s\Entity\%s');
            $finderClass = \XF::stringToClass($finder, '%s\Finder\%s');

            $entityReflection = new \ReflectionClass($entityClass);
            if (!$entityReflection->isInstantiable() || !$entityReflection->isSubclassOf('XF\Mvc\Entity\Entity'))
            {
                continue;
            }

            if (class_exists($finderClass))
            {
                $finderReflection = new \ReflectionClass($finderClass);
                if (!$finderReflection->isInstantiable() || !$finderReflection->isSubclassOf('XF\Mvc\Entity\Finder'))
                {
                    continue;
                }

                $path = realpath(\XF::$autoLoader->findFile($finderClass));
                $contents = file_get_contents($path);

                $shortName = $finderReflection->getShortName();
                $existingComment = $finderReflection->getDocComment();
            }
            else
            {
                $path = realpath(\XF::$autoLoader->findFile($entityClass));
                $ds = \DIRECTORY_SEPARATOR;
                $path = str_replace("{$ds}Entity{$ds}", "{$ds}Finder{$ds}", $path);

                $shortName = $entityReflection->getShortName();
                $contents = $this->getFallbackFinderFileContents($finderClass, $shortName);
                $existingComment = null;
            }

            $structure = $entityClass::getStructure(new EntityStructure());

            $output->writeln("Writing class properties for entity $finder");

            $docPlaceholder = $this->getDocPlaceholder();

            if (!$existingComment)
            {
                $search = 'class ' . $shortName . ' extends ';
                $replace = "$docPlaceholder\n$search";
                $newContents = str_replace($search, $replace, $contents);
            }
            else
            {
                $newContents = str_replace($existingComment, $docPlaceholder, $contents);
            }

            $relations = [];
            foreach ($structure->relations AS $relation => $def)
            {
                $relations[$relation] = $this->getTypeHintForClass(
                    $finderClass,
                    $addOnId,
                    $requireAddOnIds,
                    $softRequireAddOnIds,
                    'XF\Mvc\Entity\Finder'
                );
            }
            if (!$relations)
            {
                $output->writeln("No changes made for finder $finder");
                $output->writeln("");
                return 0;
            }

            $newComment = '/**' . "\n";
            $newComment .= ' * RELATIONS';
            foreach ($relations AS $relation => $type)
            {
                $newComment .= "\n" . ' * @property-read' . ' ' . implode('|', $type) . ' $' . $relation;
            }
            $newComment .= "\n */";
            $newContents = str_replace($docPlaceholder, $newComment, $newContents);

            if (FileUtil::writeFile($path,$newContents, false))
            {
                $output->writeln("Written out class properties for finder $finder");
            }
            else
            {
                $output->writeln("Could not write out class properties for finder $finder");
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

    protected function getFallbackFinderFileContents(string $addOnPath, string $class) : string
    {
        $namespace = explode("\\$class", $addOnPath)[0];

        return <<<PHP
<?php

namespace {$namespace};

use XF\Mvc\Entity\Finder;

class {$class} extends Finder
{
}
PHP;

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