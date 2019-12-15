<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Mvc\Reply\View as ViewReply;
use XF\Service\AddOn\ReleaseBuilder as ReleaseBuilderSvc;

/**
 * Class AddOn
 *
 * @package TickTackk\DeveloperTools
 */
class AddOn extends XFCP_AddOn
{
    /**
     * @param ParameterBag $params
     *
     * @return ViewReply
     * @throws ExceptionReply
     * @throws \ErrorException
     * @throws \XF\PrintableException
     */
    public function actionBuild(ParameterBag $params) : ViewReply
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $addOn = $this->assertAddOnAvailable($params->addon_id_url);

        /** @var ReleaseBuilderSvc $builderService */
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

        return $this->view('TickTackk\DeveloperTools\XF:AddOn\Build', '', $viewParams);
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @return RedirectReply|ViewReply
     * @throws ExceptionReply
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
            'addOn' => $addOn
        ];
        return $this->view('TickTackk\DeveloperTools\XF:AddOn\DeveloperOptions', 'developerTools_developer_options', $viewParams);
    }
}