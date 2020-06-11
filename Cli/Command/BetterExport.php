<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;

/**
 * Class BetterExport
 *
 * @package TickTackk\DeveloperTools
 */
class BetterExport extends Command
{
    use AddOnActionTrait;

    protected function configure() : void
    {
        $this
            ->setName('tck-devtools:better-export')
            ->setAliases(['tck-dt:better-export'])
            ->setDescription('Exports the XML files for an add-on and applies class properties to type hint columns, getters and relations')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            )
            ->addOption(
                'skip-export',
                's',
                InputOption::VALUE_NONE,
                'Skip \'xf-dev:export\' command'
            )
            ->addOption(
                'release',
                'r',
                InputOption::VALUE_NONE,
                'Run \'xf-addon:build-release\' command'
            )
            ->addOption(
                'readme',
                'd',
                InputOption::VALUE_NONE,
                'Run \'tck-devtools:build-readme\' command'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Exception
     */
    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        $id = $input->getArgument('id');

        $addOn = $this->checkEditableAddOn($id, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $command = $this->getApplication()->find('tck-devtools:clamp-versions');
        $childInput = new ArrayInput([
            'command' => 'tck-devtools:clamp-versions',
            'id' => $addOn->getAddOnId()
        ]);
        $command->run($childInput, $output);

        $command = $this->getApplication()->find('xf-addon:export');
        $childInput = new ArrayInput([
            'command' => 'xf-addon:export',
            'id' => $addOn->getAddOnId()
        ]);
        $command->run($childInput, $output);

        $entityPath = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . 'Entity';
        if (\is_dir($entityPath))
        {
            $command = $this->getApplication()->find('xf-dev:entity-class-properties');
            $childInput = new ArrayInput([
                'command' => 'xf-dev:entity-class-properties',
                'addon-or-entity' => $addOn->getAddOnId()
            ]);
            $command->run($childInput, $output);
        }

        $skipExport = $input->getOption('skip-export');
        if (!$skipExport)
        {
            $command = $this->getApplication()->find('xf-dev:export');
            $childInput = new ArrayInput([
                'command' => 'xf-dev:export',
                '--addon' => $addOn->getAddOnId()
            ]);
            $command->run($childInput, $output);
        }

        $release = $input->getOption('release');
        if ($release)
        {
            $command = $this->getApplication()->find('xf-addon:build-release');
            $childInput = new ArrayInput([
                'command' => 'xf-addon:build-release',
                'id' => $addOn->getAddOnId()
            ]);
            $command->run($childInput, $output);
        }

        $readme = $input->getOption('readme');
        if ($readme)
        {
            $command = $this->getApplication()->find('tck-devtools:build-readme');
            $childInput = new ArrayInput([
                'command' => 'tck-devtools:build-readme',
                'id' => $addOn->getAddOnId(),
                '--markdown' => true,
            ]);
            $command->run($childInput, $output);
        }

        return 0;
    }
}