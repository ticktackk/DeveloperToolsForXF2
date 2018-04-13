<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Mvc\ParameterBag;

class AddOn extends XFCP_AddOn
{
    public function actionDeveloperOptions(ParameterBag $params)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $addOn = $this->assertAddOnAvailable($params->addon_id_url);

        $viewParams = [
            'addOn' => $addOn
        ];
        return $this->view('TickTackk\DeveloperTools\XF:AddOn\DeveloperOptions', 'developerTools_developer_options', $viewParams);
    }

    public function actionSaveDeveloperOptions(ParameterBag $params)
    {
        $this->assertPostOnly();

        /** @noinspection PhpUndefinedFieldInspection */
        $addOn = $this->assertAddOnAvailable($params->addon_id_url);

        $input = $this->filter([
            'devTools_license' => 'str',
            'devTools_gitignore' => 'str',
            'devTools_readme_md' => 'str',
            'devTools_parse_additional_files' => 'bool'
        ]);

        $addOnId = $addOn->getAddOnId();
        $addOnEntity = $addOn->getInstalledAddOn();
        $addOnEntity->bulkSet($input);
        $addOnEntity->save();

        return $this->redirect($this->buildLink('add-ons'));
    }
}