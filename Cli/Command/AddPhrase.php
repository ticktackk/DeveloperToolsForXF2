<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;
use XF\Entity\Phrase as PhraseEntity;

/**
 * Class AddPhrase
 *
 * @package TickTackk\DeveloperTools\Cli\Command\Phrase
 */
class AddPhrase extends Command
{
    use AddOnActionTrait;

    protected function configure() : void
    {
        $this->setName('tck-devtools:add-phrase')
            ->setAliases(['tck-dt:add-phrase'])
            ->setDescription('Creates a phrase for an add-on.')
            ->addArgument('id', InputArgument::REQUIRED, 'Add-on ID')
            ->addArgument('title', InputArgument::REQUIRED, 'Title')
            ->addArgument('text', InputArgument::REQUIRED, 'Text');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|int
     * @throws \XF\PrintableException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getArgument('id');

        $addOn = $this->checkEditableAddOn($id, $error);
        if (!$addOn)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        /** @var PhraseEntity $phrase */
        $phrase = \XF::app()->em()->create('XF:Phrase');
        $phrase->title = $input->getArgument('title');
        $phrase->phrase_text = $input->getArgument('text');
        $phrase->addon_id = $id;
        $phrase->language_id = 0;
        $phrase->save();

        $output->writeln('Done.');
        return 0;
    }
}