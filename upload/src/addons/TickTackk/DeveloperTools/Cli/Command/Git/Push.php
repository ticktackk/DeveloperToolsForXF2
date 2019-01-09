<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Bit3\GitPhp\GitRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;
use TickTackk\DeveloperTools\Cli\Command\DevToolsActionTrait;

/**
 * Class Push
 *
 * @package TickTackk\DeveloperTools
 */
class Push extends Command
{
    use AddOnActionTrait;
    use DevToolsActionTrait;

    protected function configure() : void
    {
        $this
            ->setName('ticktackk-devtools:git-push')
            ->setDescription('Push changes to the current tracking branch')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
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
                null
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
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

        $repoRoot = $this->getAddOnRepoDir($addOn);

        $git = new GitRepository($repoRoot);
        if (!$git->isInitialized())
        {
            $command = $this->getApplication()->find('ticktackk-devtools:git-init');
            $childInput = new ArrayInput([
                'command' => 'ticktackk-devtools:git-init',
                'id' => $addOn->getAddOnId()
            ]);
	    $command->run($childInput, $output);
        }
    
        $repo = $input->getOption('repo');
        $branch = $input->getOption('branch');
        
        // Passing null as second argument doesn't work for some reason
        if ($branch)
        {
            $git->push()->execute($repo, $branch);
            $output->writeln(['', "Successfully pushed to {$repo}/{$branch}."]);
            
        }
        else
        {
            $git->push()->execute($repo);
            $output->writeln(['', "Successfully pushed to {$repo}."]);
        }

        return 0;
    }
}