<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\AddOn\AddOn;
use XF\Cli\Command\AddOnActionTrait;
use Symfony\Component\Console\Input\InputOption;
use XF\Cli\Command\JobRunnerTrait;

/**
 * Class Seeder
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
class Seeder extends Command
{
    use AddOnActionTrait, JobRunnerTrait;

    protected function configure() : void
    {
        $this
            ->setName('ticktackk-devtools:seed')
            ->setDescription('Runs all the seeds to fill your forum with dummy data.')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            )
            ->addOption(
                'specific-seed',
                't',
                InputOption::VALUE_OPTIONAL,
                'Run specific seed instead of all'
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

        $specificSeed = $input->getOption('specific-seed');
        if (!empty($specificSeed))
        {
            $seeds = [$specificSeed];
        }
        else
        {
            $seeds = $this->getSeedClasses($addOn);
        }

        $this->setupAndRunJob('tckDevToolsSeed', 'TickTackk\DeveloperTools:Seed', [
            'seeds' => $seeds
        ], $output);

        $output->write('Successfully seeded.');

        return 0;
    }

    /**
     * @param AddOn $addOn
     *
     * @return array
     */
    protected function getSeedClasses(AddOn $addOn) : array
    {
        $ds = DIRECTORY_SEPARATOR;
        $filesIterator = $this->getFileIterator($addOn->getAddOnDirectory() . $ds . 'Seed');
        $classes = [];

        foreach ($filesIterator AS $file)
        {
            $fileName = $file->getBasename('.' . $file->getExtension());
            if ($fileName !== 'AbstractSeed' && is_readable($file->getPathname()))
            {
                $classes[] = $addOn->prepareAddOnIdForClass() . ':' . $fileName;
            }
        }

        return $classes;
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
}