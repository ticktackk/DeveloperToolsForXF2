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
        if ($this->schemaManager()->columnExists('xf_user', 'license'))
        {
            $this->schemaManager()->alterTable('xf_user', function(Alter $table)
            {
                $table->dropColumns('license');
            });
        }

        if ($this->schemaManager()->columnExists('xf_user', 'gitignore'))
        {
            $this->schemaManager()->alterTable('xf_user', function(Alter $table)
            {
                $table->dropColumns('gitignore');
            });
        }

        if ($this->schemaManager()->columnExists('xf_user', 'readme_md'))
        {
            $this->schemaManager()->alterTable('xf_user', function(Alter $table)
            {
                $table->dropColumns('readme_md');
            });
        }

        $this->schemaManager()->alterTable('xf_addon', function(Alter $table)
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