<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TickTackk\DeveloperTools\Git\GitRepository;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File;

class Push extends Command
{
    use AddOnActionTrait;

    protected function configure()
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
     */
    /** @noinspection PhpMissingParentCallCommonInspection */
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

        File::createDirectory($repoRoot);

        $git = new GitRepository($repoRoot);
        if (!$git->isInitialized())
        {
            $output->writeln(["", "Git directory must be initialized"]);
            return 0;
        }
    
        $repo = $input->getOption('repo');
        $branch = $input->getOption('branch');
        
        // Passing null as second argument doesn't work for some reason
        if ($branch)
        {
            $git->push()->execute($repo, $branch);
            $output->writeln(["", "Successfully pushed to {$repo}/{$branch}."]);
            
        }
        else
        {
            $git->push()->execute($repo);
            $output->writeln(["", "Successfully pushed to {$repo}."]);
        }

        return 0;
    }
}