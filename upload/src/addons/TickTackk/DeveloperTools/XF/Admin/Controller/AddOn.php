<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Mvc\ParameterBag;

/**
 * Class AddOn
 *
 * @package TickTackk\DeveloperTools
 */
class AddOn extends XFCP_AddOn
{
    /**
     * @param ParameterBag $parameterBag
     *
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDeveloperOptions(ParameterBag $parameterBag)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $addOn = $this->assertAddOnAvailable($parameterBag->addon_id_url);

        if (!$addOn->canEdit())
        {
            return $this->noPermission();
        }

        /** @var \TickTackk\DeveloperTools\XF\Repository\AddOn $addOnRepo */
        $addOnRepo = $this->repository('XF:AddOn');

        if ($this->isPost())
        {
            $input = $this->filter([
                'license' => 'str',
                'gitignore' => 'str',
                'readme' => 'str',
                'parse_additional_files' => 'bool',
                'git' => 'array-string'
            ]);

            $addOnRepo->exportDeveloperOptions($addOn->getInstalledAddOn(), $input);

            return $this->redirect($this->buildLink('add-ons'));
        }

        $fakeComposerCELExists = $this->finder('XF:CodeEventListener')
            ->where('callback_class', $addOn->prepareAddOnIdForClass() . '\\FakeComposer')
            ->where('callback_method', 'appSetup')
            ->where('addon_id', $addOn->getAddOnId())
            ->fetchOne();
        $fakeComposerFile = $addOn->getAddOnDirectory() . DIRECTORY_SEPARATOR . 'FakeComposer.php';
        $fakeComposerFileUsable = file_exists($fakeComposerFile) && is_readable($fakeComposerFile);

        $viewParams = [
            'addOn' => $addOn,
            'allowFakeComposerRebuild' => $fakeComposerCELExists && $fakeComposerFileUsable
        ];
        return $this->view('TickTackk\DeveloperTools\XF:AddOn\DeveloperOptions', 'developerTools_developer_options', $viewParams);
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionRebuildFakeComposer(ParameterBag $parameterBag)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $addOn = $this->assertAddOnAvailable($parameterBag->addon_id_url);

        if (!$addOn->canEdit())
        {
            return $this->noPermission();
        }

        /** @var \TickTackk\DeveloperTools\Service\Autoload\Creator $classMapService */
        $classMapService = $this->service('TickTackk\DeveloperTools:Autoload\Creator', $addOn->getInstalledAddOn());
        $classMapService->build();

        return $this->redirect($this->buildLink('add-ons/developer-options', $addOn->getInstalledAddOn()));
    }
}