<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;

/**
 * Class RebuildFakeComposer
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
class RebuildFakeComposer extends Command
{
    use AddOnActionTrait;

    protected function configure() : void
    {
        $this
            ->setName('ticktackk-devtools:rebuild-fake-composer')
            ->setDescription('Rebuilds FakeComposer file for an add-on')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \XF\PrintableException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : ? int
    {
        $addOnId = $input->getArgument('id');
        $addOn = $this->checkEditableAddOn($addOnId, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $ds = DIRECTORY_SEPARATOR;
        $fakeComposerFile = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . 'FakeComposer.php';
        $fakeComposerFileExists = file_exists($fakeComposerFile);

        if (!is_dir($addOn->getAddOnDirectory() . $ds . 'vendor'))
        {
            if ($fakeComposerFileExists)
            {
                $output->writeln(['', 'No vendor directory available. Removing existing FakeComposer.']);
                unlink($fakeComposerFile);
            }
            else
            {
                $output->writeln(['', 'No vendor directory available.']);
            }

            return 0;
        }

        if ($fakeComposerFileExists)
        {
            $output->writeln(['', 'Rebuilding FakeComposer...']);
        }
        else
        {
            $output->writeln(['', 'Creating FakeComposer file...']);
        }

        /** @var \TickTackk\DeveloperTools\Service\FakeComposer\Creator $fakeComposerCreator */
        $fakeComposerCreator = \XF::service('TickTackk\DeveloperTools:FakeComposer\Creator', $addOn->getInstalledAddOn());
        $fakeComposerCreator->build();

        $output->writeln(['', 'FakeComposer file rebuilt']);

        return 0;
    }
}