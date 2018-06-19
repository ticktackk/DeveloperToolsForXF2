<?php

namespace TickTackk\DeveloperTools;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

/**
 * Class Setup
 *
 * @package TickTackk\DeveloperTools
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $this->schemaManager()->alterTable('xf_addon', function (Alter $table)
        {
            $table->addColumn('devTools_license', 'mediumtext');
            $table->addColumn('devTools_gitignore', 'mediumtext');
            $table->addColumn('devTools_readme_md', 'mediumtext');
            $table->addColumn('devTools_parse_additional_files', 'tinyint')->setDefault(0);
        });
    }

    public function upgrade1000010Step1()
    {
        $sm = $this->schemaManager();
        if ($sm->columnExists('xf_user', 'license'))
        {
            $sm->alterTable('xf_user', function (Alter $table)
            {
                $table->dropColumns('license');
            });
        }

        if ($sm->columnExists('xf_user', 'gitignore'))
        {
            $sm->alterTable('xf_user', function (Alter $table)
            {
                $table->dropColumns('gitignore');
            });
        }

        if ($sm->columnExists('xf_user', 'readme_md'))
        {
            $sm->alterTable('xf_user', function (Alter $table)
            {
                $table->dropColumns('readme_md');
            });
        }

        $sm->alterTable('xf_addon', function (Alter $table)
        {
            $table->addColumn('license', 'mediumtext');
            $table->addColumn('gitignore', 'mediumtext');
            $table->addColumn('readme_md', 'mediumtext');
        });
    }
    
    public function upgrade1000030Step1()
    {
        $sm = $this->schemaManager();
        $sm->alterTable('xf_addon', function (Alter $table)
        {
            $table->renameColumn('license', 'devTools_license');
            $table->renameColumn('gitignore', 'devTools_gitignore');
            $table->renameColumn('readme_md', 'devTools_readme_md');
            
            $table->addColumn('devTools_parse_additional_files', 'tinyint')->setDefault(0);
        });
    }
    
    public function uninstallStep1()
    {
        $this->schemaManager()->alterTable('xf_addon', function (Alter $table)
        {
            $table->dropColumns(['devTools_license', 'devTools_gitignore', 'devTools_readme_md', 'devTools_parse_additional_files']);
        });
    }
}