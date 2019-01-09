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
            ->setName('ticktackk-devtools:better-export')
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
                'skip-tests',
                't',
                InputOption::VALUE_NONE,
                'Skip \'ticktackk-devtools:phpunit\' command'
            )
            ->addOption(
                'move',
                'm',
                InputOption::VALUE_NONE,
                'Run \'ticktackk-devtools:git-move\' command'
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
            )
            ->addOption(
                'push',
                'p',
                InputOption::VALUE_NONE,
                'Run \'ticktackk-devtools:git-push\' command'
            )
            ->addOption(
                'repo',
                null,
                InputOption::VALUE_OPTIONAL,
                'Repository to push to',
                'origin'
            )
            ->addOption(
                'branch',
                null,
                InputOption::VALUE_OPTIONAL,
                'Branch to push to',
                'master'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Exception
     */
    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output) : ? int
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

        $entityPath = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . 'Entity';
        if (is_dir($entityPath))
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

        $skipTests = $input->getOption('skip-tests');
        if (!$skipTests)
        {
            $command = $this->getApplication()->find('ticktackk-devtools:phpunit');
            $childInput = new ArrayInput([
                'command' => 'ticktackk-devtools:phpunit',
                'id' => $addOn->getAddOnId()
            ]);
            $phpUnitResult = $command->run($childInput, $output);
            if ($phpUnitResult !== 0)
            {
                return $phpUnitResult;
            }
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

        $move = $input->getOption('move');
        if ($move)
        {
            $command = $this->getApplication()->find('ticktackk-devtools:git-move');
            $childInput = new ArrayInput([
                'command' => 'ticktackk-devtools:git-move',
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
    
        $push = $input->getOption('push');
        if ($push)
        {
            $command = $this->getApplication()->find('ticktackk-devtools:git-push');
            $childInput = new ArrayInput([
                'command' => 'ticktackk-devtools:git-push',
                'id' => $addOn->getAddOnId(),
                '--repo' => $input->getOption('repo'),
                '--branch' => $input->getOption('branch')
            ]);
            $command->run($childInput, $output);
        }

        return 0;
    }
}