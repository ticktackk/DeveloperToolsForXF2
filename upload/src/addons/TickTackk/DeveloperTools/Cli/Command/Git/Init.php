<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TickTackk\DeveloperTools\Git\GitRepository;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File;

class Init extends Command
{
    use AddOnActionTrait;

    protected function configure()
    {
        $this
            ->setName('ticktackk-devtools:git-init')
            ->setDescription('Initialize an add-on for VCS')
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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');

        $addOn = $this->checkEditableAddOn($id, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $addOnDirectory = $addOn->getAddOnDirectory();
        $ds = DIRECTORY_SEPARATOR;
        $repoRoot = $addOnDirectory . $ds . '_repo';

        if (is_dir($repoRoot))
        {
            $output->writeln("Add-on repository directory has already been initialized.");
            return 1;
        }

        File::createDirectory($repoRoot);

        $git = new GitRepository($repoRoot);
        $git->init()->execute();

        $output->writeln(["", "Successfully initialized git."]);
        return 0;
    }
}