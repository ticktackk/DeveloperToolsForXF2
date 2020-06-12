<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TickTackk\DeveloperTools\Service\AddOn\ReadmeBuilder as AddOnReadmeBuilderSvc;
use XF\AddOn\AddOn;
use XF\Cli\Command\AddOnActionTrait;
use XF\Cli\Command\Development\RequiresDevModeTrait;
use XF\App as BaseApp;
use XF\Service\AbstractService;

/**
 * Class BuildReadme
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
class BuildReadme extends Command
{
    use RequiresDevModeTrait, AddOnActionTrait;

    protected function configure() : void
    {
        $this
            ->setName('tck-devtools:build-readme')
            ->setAliases(['tck-dt:build-readme'])
            ->setDescription('Builds README files for provided add-on.')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Add-On ID'
            )
            ->addOption(
                'markdown',
                'm',
                InputOption::VALUE_NONE,
                'Builds Markdown version of the README file.'
            )
            ->addOption(
                'html',
                't',
                InputOption::VALUE_NONE,
                'Builds HTML version of the README file.'
            )
            ->addOption(
                'bbcode',
                'b',
                InputOption::VALUE_NONE,
                'Builds BBCode version of the README file.'
            )
            ->addOption(
                'copy',
                'c',
                InputOption::VALUE_NONE,
                'Also copies the resulting file(s) to the \'_no_upload\' directory.'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $addOnId = $input->getArgument('id');
        $addOnObj = $this->checkEditableAddOn($addOnId, $error);
        if (!$addOnObj)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $types = [];
        if ($input->getOption('markdown'))
        {
            $types[] = 'md';
        }
        if ($input->getOption('html'))
        {
            $types[] = 'html';
        }
        if ($input->getOption('bbcode'))
        {
            $types[] = 'bb_code';
        }

        if (empty($types))
        {
            $output->writeln('<error>You must specify at least one readme type!</error>');
            return 1;
        }

        $readMeGeneratorSvc = $this->getReadMeGeneratorSvc($addOnObj, $types, (bool)$input->getOption('copy'));
        if (!$readMeGeneratorSvc->validate($errors))
        {
            foreach ($errors AS $error)
            {
                $output->writeln('<error>' . $error . '</error>');
            }

            return 1;
        }

        $readMeGeneratorSvc->save();
        $output->writeln(["", "Successfully built readme files."]);

        return 0;
    }

    /**
     * @return BaseApp
     */
    protected function app() : BaseApp
    {
        return \XF::app();
    }

    /**
     * @param string $identifier
     * @param mixed ...$arguments
     *
     * @return AbstractService
     */
    protected function service(string $identifier, ...$arguments) : AbstractService
    {
        return $this->app()->service($identifier, ...$arguments);
    }

    /**
     * @param AddOn $addOn
     * @param array $types
     * @param bool $copy
     *
     * @return AbstractService|AddOnReadmeBuilderSvc
     */
    protected function getReadMeGeneratorSvc(AddOn $addOn, array $types = [], bool $copy = false) : AddOnReadmeBuilderSvc
    {
        return $this->service('TickTackk\DeveloperTools:AddOn\ReadmeBuilder', $addOn, $types, $copy);
    }
}