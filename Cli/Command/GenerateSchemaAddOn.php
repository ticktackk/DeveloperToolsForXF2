<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\Development\RequiresDevModeTrait;

class GenerateSchemaAddOn extends Command
{
    use RequiresDevModeTrait;

    protected function configure()
    {
        $this
            ->setName('tck-devtools:generate-schema-addon')
            ->setDescription('Generates schema codes from add-on.')
            ->setAliases(['tck-dt:generate-schema-addon'])
            ->addArgument(
                'addon-or-entity',
                InputArgument::REQUIRED,
                'Add-on ID or specific Entity short name to generate Entity class schema code for.'
            )
            ->addOption(
                'sv-standard-lib',
                null,
                InputOption::VALUE_NONE,
                'If set, schema codes will be generated for usage with Standard Library by Xon'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entities = [];

        $addOnOrEntity = $input->getArgument('addon-or-entity');
        if (strpos($addOnOrEntity, ':') !== false)
        {
            $entities[] = $addOnOrEntity;
        }
        else
        {
            if ($addOnOrEntity === 'XF')
            {
                $path = \XF::getSourceDirectory() . \XF::$DS . 'XF' . \XF::$DS . 'Entity';
                $addOnId = 'XF';
            }
            else
            {
                $manager = \XF::app()->addOnManager();
                $addOn = $manager->getById($addOnOrEntity);
                if (!$addOn || !$addOn->isAvailable())
                {
                    $output->writeln('Add-on could not be found.');
                    return 1;
                }

                $addOnId = $addOn->getAddOnId();
                $path = $manager->getAddOnPath($addOnId) . \XF::$DS . 'Entity';
            }

            if (!file_exists($path) || !is_dir($path))
            {
                $output->writeln('<error>The selected add-on does not appear to have an Entity directory.</error>');
                return 1;
            }

            $iterator = new \RegexIterator(
                \XF\Util\File::getRecursiveDirectoryIterator($path, null, null), '/\.php$/'
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator AS $name => $file)
            {
                $name = str_replace('.php', '', $file->getFilename());
                $subDir = substr($file->getPath(), strlen($path));
                $subDir = ltrim(str_replace('/', '\\', $subDir) . '\\', '\\');
                $entities[] = str_replace('/', '\\', (string) $addOnId) . ':' . $subDir . $name;
            }
        }

        if (!$entities)
        {
            $output->writeln('<error>No entity classes could be found.</error>');
            return 1;
        }

        foreach ($entities AS $entity)
        {
            $exporter = 'xf-dev:generate-schema-entity';
            if ($input->getOption('sv-standard-lib'))
            {
                $exporter = 'tck-devtools:generate-schema-entity';
            }

            if ($input->getOption(''))
            $command = $this->getApplication()->find($exporter);

            $i = ['command' => $exporter, 'id' => $entity];

            $childInput = new ArrayInput($i);
            $command->run($childInput, $output);
        }

        return 0;
    }
}