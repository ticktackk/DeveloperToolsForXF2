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
        $this->schemaManager()->alterTable('xf_user', function(Alter $table)
        {
            $table->addColumn('license', 'mediumtext')->setDefault('');
            $table->addColumn('gitignore', 'mediumtext')->setDefault('');
            $table->addColumn('readme_md', 'mediumtext')->setDefault('');
        });
    }
}