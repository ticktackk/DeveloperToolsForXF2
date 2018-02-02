<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;

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
            'id' => $addOn->addon_id
        ]);
        $command->run($childInput, $output);

        $command = $this->getApplication()->find('xf-dev:entity-class-properties');
        $childInput = new ArrayInput([
            'command' => 'xf-dev:entity-class-properties',
            'addon-or-entity' => $addOn->addon_id
        ]);
        $command->run($childInput, $output);

        return 0;
    }
}