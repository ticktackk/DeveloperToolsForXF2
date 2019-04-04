<?php

namespace TickTackk\DeveloperTools;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Util\File;
use XF\Util\Json;

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

    public function upgrade1000033Step1() : void
    {
    	$addOns = $this->db()->fetchAll("
    		SELECT * FROM xf_addon
    		WHERE (
    			devTools_license <> ''
    			OR devTools_gitignore <> ''
    			OR devTools_readme_md <> ''
    			OR devTools_parse_additional_files <> ''
    		)
    		  AND addon_id NOT IN('XF', 'XFRM', 'XFMG')
    	");

        if (count($addOns))
        {
            $options = $this->app->options();
            $gitName = $options->developerTools_git_username;
            $gitEmail = $options->developerTools_git_email;

            foreach ($addOns AS $addOn)
            {
            	$addOnEntity = \XF::em()->find('XF:AddOn', $addOn['addon_id']);

                if (\XF::$versionId >= 2010000)
                {
                    $addOn = new \XF\AddOn\AddOn($addOnEntity, \XF::app()->addOnManager());
                }
                else
                {
                    /** @noinspection PhpParamsInspection */
                    $addOn = new \XF\AddOn\AddOn($addOnEntity);
                }

                $addOnDir = $addOn->getAddOnDirectory();
				File::writeFile($addOnDir . DIRECTORY_SEPARATOR . 'dev.json', Json::jsonEncodePretty([
					'gitignore' => $addOn['devTools_gitignore'],
					'license' => $addOn['devTools_license'],
					'readme' => $addOn['devTools_readme_md'],
					'parse_additional_files' => (bool)$addOn['devTools_parse_additional_files']
				]), false);

				File::writeFile($addOnDir . DIRECTORY_SEPARATOR . 'git.json', Json::jsonEncodePretty([
					'name' => $gitName,
					'email' => $gitEmail
				]), false);
            }
        }
    }

    public function upgrade1000033Step2() : void
    {
        $this->schemaManager()->alterTable('xf_addon', function (Alter $table)
        {
            $table->dropColumns(['devTools_license', 'devTools_gitignore', 'devTools_readme_md', 'devTools_parse_additional_files']);
        });
    }
}