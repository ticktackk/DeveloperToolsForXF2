<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ClampVersions extends Command
{
    protected function configure()
    {
        $this
            ->setName('tck-devtools:clamp-versions')
            ->setAliases(['tck-dt:clamp-versions'])
            ->setDescription('Ensures an add-on does not have phrases or templates with version id\'s above the addon.json file.')
            ->addArgument('id', InputArgument::OPTIONAL, 'Add-On ID')
        ;
    }

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

        $addOnObj = new \XF\AddOn\AddOn($addOnId, \XF::app()->addOnManager());
        $jsonPath = $addOnObj->getJsonPath();

        if (!file_exists($jsonPath))
        {
            $output->writeln("<error>The addon.json file must exist at {$jsonPath}.</error>");

            return 1;
        }

        $versionData = $addOnObj->getJsonVersion();

        $db = \XF::db();
        $statement = $db->query('UPDATE xf_phrase SET version_id = ?, version_string = ? WHERE version_id >= ? AND addon_id = ?', [
            $versionData['version_id'], $versionData['version_string'], $versionData['version_id'], $addOnObj->getAddOnId()
        ]);
        $rowCount = $statement->rowsAffected();
        if ($rowCount)
        {
            $output->writeln("Updated {$rowCount} phrases with too new versions to {$versionData['version_string']}");
        }

        $db->query('UPDATE xf_template SET version_id = ?, version_string = ? WHERE version_id >= ? AND addon_id = ?', [
            $versionData['version_id'], $versionData['version_string'], $versionData['version_id'], $addOnObj->getAddOnId()
        ]);
        $rowCount = $statement->rowsAffected();
        if ($rowCount)
        {
            $output->writeln("Updated {$rowCount} templates with too new versions to {$versionData['version_string']}");
        }

        return 0;
    }

    /**
     * @param $key
     * @return \Closure
     */
    protected function getAddOnQuestionFieldValidator($key)
    {
        return function ($value) use ($key) {
            $addOn = \XF::em()->create('XF:AddOn');

            $valid = $addOn->set($key, $value);
            if (!$valid)
            {
                $errors = $addOn->getErrors();
                if (isset($errors[$key]))
                {
                    throw new \InvalidArgumentException($errors[$key]);
                }
            }

            return $value;
        };
    }
}
