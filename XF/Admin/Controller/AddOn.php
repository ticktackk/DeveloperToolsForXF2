<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Mvc\Entity\Entity;
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
}