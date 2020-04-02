<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\AddOn\Manager as AddOnManager;
use XF\App as BaseApp;
use XF\Behavior\DevOutputWritable as DevOutputWritableBehavior;
use XF\Cli\Command\AddOnActionTrait;
use XF\Db\AbstractAdapter as DbAdapter;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager as EntityManager;
use XF\DevelopmentOutput as DevelopmentOutput;

/**
 * Class ClampVersions
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
class ClampVersions extends Command
{
    use AddOnActionTrait;

    protected function configure() : void
    {
        $this
            ->setName('tck-devtools:clamp-versions')
            ->setAliases(['tck-dt:clamp-versions'])
            ->setDescription('Ensures an add-on does not have phrases or templates with version id\'s above the addon.json file.')
            ->addArgument('id', InputArgument::REQUIRED, 'Add-On ID')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \XF\PrintableException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $addOnId = $input->getArgument('id');
        $addOnObj = $this->checkEditableAddOn($addOnId, $error);
        if (!$addOnObj)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $addOnId = $addOnObj->getAddOnId();
        $jsonPath = $addOnObj->getJsonPath();

        if (!\file_exists($jsonPath))
        {
            $output->writeln("<error>The addon.json file must exist at {$jsonPath}.</error>");

            return 1;
        }

        $entityMaps = [
            'XF:Phrase' => 'phrases',
            'XF:Template' => 'templates'
        ];
        ['version_id' => $versionId, 'version_string' => $versionString] = $addOnObj->getJsonVersion();

        $totalUpdated = 0;
        foreach ($entityMaps AS $identifier => $friendlyName)
        {
            $this->clampVersionFor($output, $identifier, $friendlyName, $addOnId, $versionId, $versionString, $totalUpdated);
        }

        if (!$totalUpdated)
        {
            $output->writeln('No phrases or templates were updated.');
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param string $identifier
     * @param string $friendlyName
     * @param string $addOnId
     * @param int $versionId
     * @param string $versionString
     * @param int $totalUpdated
     *
     * @throws \XF\PrintableException
     */
    protected function clampVersionFor(OutputInterface $output, string $identifier, string $friendlyName, string $addOnId, int $versionId, string $versionString, int &$totalUpdated) : void
    {
        $start = microtime(true);

        $finder = $this->entityManager()->getFinder($identifier)
            ->where('addon_id', $addOnId)
            ->where('version_id', '>=', $versionId);

        $total = $finder->total();
        if (!$total)
        {
            return;
        }

        $output->writeln("Exporting $friendlyName...");
        $progress = new ProgressBar($output, $total);

        $devOutput = $this->developmentOutput();
        $devOutput->enableBatchMode();

        /** @var Entity $entity */
        foreach ($finder->fetch() AS $entity)
        {
            $entity->bulkSet([
                'version_id' => $versionId,
                'version_string' => $versionString
            ]);

            /** @var DevOutputWritableBehavior $devOutputWritable */
            $devOutputWritable = $entity->getBehavior('XF:DevOutputWritable');
            $devOutputWritable->setOption('write_dev_output', false);

            $entity->save();
            $devOutput->export($entity);
            $progress->advance();
        }

        $progress->finish();
        $output->writeln("");
        $devOutput->clearBatchMode();

        $output->writeln(\sprintf(ucfirst($friendlyName) . " exported. (%.02fs)",
            \microtime(true) - $start
        ));
    }

    /**
     * @return BaseApp
     */
    protected function app() : BaseApp
    {
        return \XF::app();
    }

    /**
     * @return EntityManager
     */
    protected function entityManager() : EntityManager
    {
        return $this->app()->em();
    }

    /**
     * @return DevelopmentOutput
     */
    protected function developmentOutput() : DevelopmentOutput
    {
        return $this->app()->developmentOutput();
    }
}
