<?php

namespace TickTackk\DeveloperTools\Cli\Command\Git;

use Bit3\GitPhp\GitException;
use Bit3\GitPhp\GitRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\Cli\Command\AddOnActionTrait;
use TickTackk\DeveloperTools\Cli\Command\DevToolsActionTrait;

/**
 * Class Commit
 *
 * @package TickTackk\DeveloperTools
 */
class Commit extends Command
{
    use AddOnActionTrait;
    use DevToolsActionTrait;

    protected function configure() : void
    {
        $this
            ->setName('ticktackk-devtools:git-commit')
            ->setDescription('Copies changes made to the add-on to repository and then finally commits the changes')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            )
            ->addOption(
                'message',
                null,
                InputOption::VALUE_OPTIONAL,
                'Commit message'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Exception
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
		
        if (empty($git->status()->getIndexStatus()))
        {
            $output->writeln(['', 'Nothing to commit.']);
            return 0;
        }

        $commitMessage = $input->getOption('message');
        if (!$commitMessage)
        {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
	
            $question = new Question('<question>Commit summary:</question> ');
            $commitMessage = $helper->ask($input, $output, $question);
            $output->writeln('');
        }

        try
        {
            $git->commit()->message($commitMessage)->execute();
        }
        catch (GitException $e)
        {
            $output->writeln(['', $e->getMessage()]);
            return 1;
        }

        $output->writeln(['', 'Successfully committed changes.']);
        return 0;
    }

    /**
     * @param $path
     *
     * @return \SplFileInfo[]|\RecursiveIteratorIterator
     */
    protected function getFileIterator($path)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path, \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * @param $rootPath
     * @param $path
     *
     * @return null|string|string[]
     */
    protected function standardizePath($rootPath, $path)
    {
        $ds = DIRECTORY_SEPARATOR;
        /** @noinspection PregQuoteUsageInspection */
        return preg_replace('#^' . preg_quote(rtrim($rootPath, $ds) . $ds) . '#', '', $path, 1);
    }

    /**
     * @param $path
     *
     * @return bool
     */
    protected function isPartOfExcludedDirectoryForRepo($path) : bool
    {
        foreach ($this->getExcludedDirectoriesForRepo() AS $dir)
        {
            if (strpos($path, $dir) === 0)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array[]|false|string[]
     */
    protected function getExcludedDirectoriesForRepo()
    {
        return preg_split('/\r?\n/', \XF::options()->developerTools_excluded_directories, -1, PREG_SPLIT_NO_EMPTY);
    }
}