<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\Cli\Command\AddOnActionTrait;

/**
 * Class Tests
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
class PHPUnit extends Command
{
    use AddOnActionTrait;

    protected function configure()
    {
        $this
            ->setName('ticktackk-devtools:phpunit')
            ->setDescription('')
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
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $addOnId = $input->getArgument('id');
        $addOn = $this->checkEditableAddOn($addOnId, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $addOnDirectory = $addOn->getAddOnDirectory();
        $testRoot = $addOnDirectory . DIRECTORY_SEPARATOR . '_tests';
        $phpunit = new \PHPUnit\TextUI\TestRunner();
        $printer = new \Codedungeon\PHPUnitPrettyResultPrinter\Printer();
        $phpunit->setPrinter($printer);

        $output->writeln(['', 'Running tests...']);
        try
        {
            $suite = $phpunit->getTest($testRoot, '', 'Test.php');
            if ($suite)
            {
                $phpunit->doRun($suite, [], false);
            }
        }
        catch (\PHPUnit\Exception $e)
        {
            $output->writeln(['', $e->getMessage()]);
            return 1;
        }

        return 0;
    }
}