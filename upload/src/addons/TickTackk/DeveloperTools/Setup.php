<?php

namespace TickTackk\DeveloperTools;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1()
    {
        $this->schemaManager()->alterTable('xf_addon', function(Alter $table)
        {
            $table->addColumn('license', 'mediumtext');
            $table->addColumn('gitignore', 'mediumtext');
            $table->addColumn('readme_md', 'mediumtext');
        });
    }

    public function upgrade1000010Step1()
    {
        $sm = $this->schemaManager();
        if ($sm->columnExists('xf_user', 'license'))
        {
            $sm->alterTable('xf_user', function(Alter $table)
            {
                $table->dropColumns('license');
            });
        }

        if ($sm->columnExists('xf_user', 'gitignore'))
        {
            $sm->alterTable('xf_user', function(Alter $table)
            {
                $table->dropColumns('gitignore');
            });
        }

        if ($sm->columnExists('xf_user', 'readme_md'))
        {
            $sm->alterTable('xf_user', function(Alter $table)
            {
                $table->dropColumns('readme_md');
            });
        }

        $sm->alterTable('xf_addon', function(Alter $table)
        {
            $table->addColumn('license', 'mediumtext');
            $table->addColumn('gitignore', 'mediumtext');
            $table->addColumn('readme_md', 'mediumtext');
        });
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->alterTable('xf_addon', function(Alter $table)
        {
            $table->dropColumns(['license', 'gitignore', 'readme_md']);
        });
    }
}