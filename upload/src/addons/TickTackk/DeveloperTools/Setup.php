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

    public function upgrade1000033Step1()
    {
        $addOns = $this->app->finder('XF:AddOn')
            ->whereOr([
                ['devTools_license', '<>', ''],
                ['devTools_gitignore', '<>', ''],
                ['devTools_readme_md', '<>', ''],
                ['devTools_parse_additional_files', '=', true]
            ])
            ->where('addon_id', '<>', [
                'XF',
                'XFRM',
                'XFMG'
            ])
            ->fetch();

        if ($addOns->count())
        {
            /** @var \TickTackk\DeveloperTools\XF\Repository\AddOn $addOnRepo */
            $addOnRepo = $this->app->repository('XF:AddOn');

            /** @var \XF\Entity\AddOn $addOn */
            foreach ($addOns AS $addOn)
            {
                $addOnRepo->exportDeveloperOptions($addOn, [
                    'license' => $addOn->get('devTools_license'),
                    'gitignore' => $addOn->get('devTools_gitignore'),
                    'readme' => $addOn->get('devTools_readme_md'),
                    'parse_additional_files' => $addOn->get('devTools_parse_additional_files')
                ]);
            }
        }
    }

    public function upgrade1000033Step2()
    {
        $this->schemaManager()->alterTable('xf_addon', function (Alter $table)
        {
            $table->dropColumns(['devTools_license', 'devTools_gitignore', 'devTools_readme_md', 'devTools_parse_additional_files']);
        });
    }
}