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
     * @param ParameterBag $params
     *
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDeveloperOptions(ParameterBag $params)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $addOn = $this->assertAddOnAvailable($params->addon_id_url);

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
                'parse_additional_files' => 'bool'
            ]);

            $addOnRepo->exportDeveloperOptions($addOn->getInstalledAddOn(), $input);

            return $this->redirect($this->buildLink('add-ons'));
        }

        $viewParams = [
            'addOn' => $addOn
        ];
        return $this->view('TickTackk\DeveloperTools\XF:AddOn\DeveloperOptions', 'developerTools_developer_options', $viewParams);
    }
}