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
    public function actionBuild(ParameterBag $params)
    {
        $addOn = $this->assertAddOnAvailable($params->addon_id_url);

        /** @var \XF\Service\AddOn\ReleaseBuilder $builderService */
        $builderService = $this->service('XF:AddOn\ReleaseBuilder', $addOn);

        $builderService->build();
        $builderService->finalizeRelease();

        $this->setResponseType('raw');

        $addOnId = $addOn->prepareAddOnIdForFilename();
        $versionString = $addOn->prepareVersionForFilename();

        $viewParams = [
            'fileName' => "$addOnId-$versionString.zip",
            'releasePath' => $addOn->getReleasePath()
        ];

        return $this->view('TickTackk\DeveloperTools:Addon\Build', '', $viewParams);
    }

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
            $developerOptions = $this->filter('dev', 'array');
            $gitConfiguration = $this->filter('git', 'array');

            $addOnRepo->exportDeveloperOptions($addOn->getInstalledAddOn(), $developerOptions);
            $addOnRepo->exportGitConfiguration($addOn->getInstalledAddOn(), $gitConfiguration);

            return $this->redirect($this->buildLink('add-ons/developer-options', $addOn->getInstalledAddOn()));
        }

        $viewParams = [
            'addOn' => $addOn,
        ];
        return $this->view('TickTackk\DeveloperTools\XF:AddOn\DeveloperOptions', 'developerTools_developer_options', $viewParams);
    }
}