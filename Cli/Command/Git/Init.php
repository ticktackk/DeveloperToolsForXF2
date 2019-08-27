<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Bit3\GitPhp\GitRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Util\File;
use XF\Cli\Command\AddOnActionTrait;
use TickTackk\DeveloperTools\Cli\Command\DevToolsActionTrait;

/**
 * Class Init
 *
 * @package TickTackk\DeveloperTools
 */
class Init extends Command
{
    use AddOnActionTrait;
    use DevToolsActionTrait;

    protected function configure() : void
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
    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getArgument('id');

        $addOn = $this->checkEditableAddOn($id, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $repoRoot = $this->getAddOnRepoDir($addOn);

        if (!is_dir($repoRoot))
        {
            File::createDirectory($repoRoot);
        }

        $git = new GitRepository($repoRoot);
        if (!$git->isInitialized())
        {
            $git->init()->execute();
        }

        $output->writeln(['', 'Successfully initialized git.']);
        return 0;
    }
}