<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File;

class BetterExport extends Command
{
    use AddOnActionTrait;

    protected function configure()
    {
        $this
            ->setName('ticktackk-devtools:better-export')
            ->setDescription('Exports the XML files for an add-on and applies class properties to type hint columns, getters and relations')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            )
            ->addOption(
                'release',
                'r',
                InputOption::VALUE_NONE,
                'Run \'xf-addon:build-release\' command'
            )
            ->addOption(
                'commit',
                'c',
                InputOption::VALUE_NONE,
                'Run \'ticktackk-devtools:git-commit\' command'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');

        $addOn = $this->checkEditableAddOn($id, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $command = $this->getApplication()->find('xf-addon:export');
        $childInput = new ArrayInput([
            'command' => 'xf-addon:export',
            'id' => $addOn->getAddOnId()
        ]);
        $command->run($childInput, $output);

        // xf 2.0.2 bug workaround
        $entityPath = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . 'Entity';
        $entityDirExists = is_dir($entityPath);
        if (!$entityDirExists)
        {
            File::createDirectory($entityPath, false);
        }

        $command = $this->getApplication()->find('xf-dev:entity-class-properties');
        $childInput = new ArrayInput([
            'command' => 'xf-dev:entity-class-properties',
            'addon-or-entity' => $addOn->getAddOnId()
        ]);
        $command->run($childInput, $output);

        if (!$entityDirExists && is_dir($entityPath))
        {
            File::deleteDirectory($entityPath);
        }

        $command = $this->getApplication()->find('xf-dev:export');
        $childInput = new ArrayInput([
            'command' => 'xf-dev:export',
            '--addon' => $addOn->getAddOnId()
        ]);
        $command->run($childInput, $output);

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

        $commit = $input->getOption('commit');
        if ($commit)
        {
            $command = $this->getApplication()->find('ticktackk-devtools:git-commit');
            $childInput = new ArrayInput([
                'command' => 'ticktackk-devtools:git-commit',
                'id' => $addOn->getAddOnId()
            ]);
            $command->run($childInput, $output);
        }

        return 0;
    }
}