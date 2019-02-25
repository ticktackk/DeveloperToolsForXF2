<?php

namespace TickTackk\DeveloperTools\Cli\Command\Phrase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\Xml;

class AddPhrase extends Command
{
	use AddOnActionTrait;

	protected function configure()
	{
		$this->setName('ticktackk-devtools:add-phrase')
			->addArgument('id', InputArgument::REQUIRED, 'Add-on ID')
			->addArgument('title', InputArgument::REQUIRED, 'Title')
			->addArgument('text', InputArgument::REQUIRED, 'Text')
			->setAliases(['tdt:phrase']);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$id = $input->getArgument('id');

		$addOn = $this->checkEditableAddOn($id, $error);
		if (!$addOn)
		{
			$output->writeln('<error>' . $error . '</error>');

			return 1;
		}

		/** @var \XF\Entity\Phrase $phrase */
		$phrase = \XF::app()->em()->create('XF:Phrase');
		$phrase->title = $input->getArgument('title');
		$phrase->phrase_text = $input->getArgument('text');
		$phrase->addon_id = $id;
		$phrase->language_id = 0;
		$phrase->save();

		$output->writeln("Done.");
		return 0;
	}
}