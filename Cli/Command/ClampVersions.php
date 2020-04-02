<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use TickTackk\DeveloperTools\Cli\Command\Exception\InvalidAddOnQuestionFieldAnswerException;
use XF\AddOn\AddOn;
use XF\AddOn\Manager as AddOnManager;
use XF\App as BaseApp;
use XF\Db\AbstractAdapter as DbAdapter;
use XF\Mvc\Entity\Manager as EntityManager;
use XF\Entity\AddOn as AddOnEntity;

/**
 * Class ClampVersions
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
class ClampVersions extends Command
{
    protected function configure() : void
    {
        $this
            ->setName('tck-devtools:clamp-versions')
            ->setAliases(['tck-dt:clamp-versions'])
            ->setDescription('Ensures an add-on does not have phrases or templates with version id\'s above the addon.json file.')
            ->addArgument('id', InputArgument::OPTIONAL, 'Add-On ID')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $addOnId = $input->getArgument('id');
        if (!$addOnId)
        {
            $question = new Question("<question>Enter the ID for the add-on:</question> ");
            $question->setValidator($this->getAddOnQuestionFieldValidator('addon_id'));
            $addOnId = $helper->ask($input, $output, $question);
            $output->writeln("");
        }

        $addOnObj = new AddOn($addOnId, $this->addOnManager());
        $jsonPath = $addOnObj->getJsonPath();

        if (!\file_exists($jsonPath))
        {
            $output->writeln("<error>The addon.json file must exist at {$jsonPath}.</error>");

            return 1;
        }

        $versionData = $addOnObj->getJsonVersion();
        $db = $this->db();

        $phrasesUpdated = $db->update('xf_phrase', [
            'version_id' => $versionData['version_id'],
            'version_string' => $versionData['version_string']
        ], 'version_id >= ? AND addon_id = ?', [$versionData['version_id'], $addOnObj->getAddOnId()]);
        if ($phrasesUpdated)
        {
            $output->writeln("Updated {$phrasesUpdated} phrases with too new versions to {$versionData['version_string']}");
        }

        $templatesUpdated = $db->update('xf_template', [
            'version_id' => $versionData['version_id'],
            'version_string' => $versionData['version_string']
        ], 'version_id >= ? AND addon_id = ?', [$versionData['version_id'], $addOnObj->getAddOnId()]);
        if ($templatesUpdated)
        {
            $output->writeln("Updated {$templatesUpdated} templates with too new versions to {$versionData['version_string']}");
        }

        return 0;
    }

    /**
     * @param string $key
     * 
     * @return \Closure
     */
    protected function getAddOnQuestionFieldValidator(string $key)
    {
        return function ($value) use ($key)
        {
            /** @var AddOnEntity $addOn */
            $addOn = $this->entityManager()->create('XF:AddOn');
            $valid = $addOn->set($key, $value);

            if (!$valid)
            {
                $errors = $addOn->getErrors();
                if (\array_key_exists($key, $errors))
                {
                    throw new InvalidAddOnQuestionFieldAnswerException($key, $errors[$key]);
                }
            }

            return $value;
        };
    }

    /**
     * @return BaseApp
     */
    protected function app() : BaseApp
    {
        return \XF::app();
    }

    /**
     * @return AddOnManager
     */
    protected function addOnManager() : AddOnManager
    {
        return $this->app()->addOnManager();
    }

    /**
     * @return DbAdapter
     */
    protected function db() : DbAdapter
    {
        return $this->app()->db();
    }

    /**
     * @return EntityManager
     */
    protected function entityManager() : EntityManager
    {
        return $this->app()->em();
    }
}
