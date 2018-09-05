<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TickTackk\DeveloperTools\Seed\SampleSeed;
use XF\AddOn\AddOn;
use XF\Cli\Command\AddOnActionTrait;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class Seeder
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
class Seeder extends Command
{
    use AddOnActionTrait;

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
        $seedClasses = $this->getSeedClasses($specificSeed, $addOn);

        foreach ($seedClasses AS $seedClass)
        {
            $seed = $this->seed($seedClass);
            $seed->run();
        }

        $output->writeln(['', 'Successfully seeded.']);

        return 0;
    }

    /**
     * @param string $specificSeed
     * @param AddOn $addOn
     *
     * @return array
     */
    protected function getSeedClasses($specificSeed, AddOn $addOn)
    {
        if (!empty($specificSeed))
        {
            return [$addOn->prepareAddOnIdForClass() . '\\Seeds\\' . $specificSeed];
        }

        $ds = DIRECTORY_SEPARATOR;
        $filesIterator = $this->getFileIterator($addOn->getAddOnDirectory() . $ds . '_Seeds');
        $classes = [];
        foreach ($filesIterator AS $file)
        {
            if ($file->isFile() && $file->getFilename() !== 'AbstractSeed.php' &&
                $this->isSeed($file->getFilename()) &&
                is_readable($file->getPathname())
            )
            {
                require $file->getPathname();
                $className = str_ireplace([$ds, '_seeds', '\\\\'], ['\\', '', ':'], utf8_substr($file->getPathname(), utf8_strlen(\XF::getAddOnDirectory() . $ds)));
                $classes[] = substr($className, 0, utf8_strlen($className) - 4);
            }
        }

        return $classes;
    }

    /**
     * @param        $fileName
     * @param string $seedSuffix
     *
     * @return bool
     */
    protected function isSeed($fileName, $seedSuffix = 'Seed.php') : bool
    {
        return strrpos($fileName, $seedSuffix, 0) === utf8_strlen($fileName) - utf8_strlen($seedSuffix);
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
     * @param $class
     *
     * @return \TickTackk\DeveloperTools\Seed\AbstractSeed
     */
    protected function seed($class)
    {
        $app = \XF::app();

        $arguments = \func_get_args();
        unset($arguments[0]);

        return $app->create('seed', $class, $arguments);
    }
}