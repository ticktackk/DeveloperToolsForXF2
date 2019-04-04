<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Diff;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\View;
use TickTackk\DeveloperTools\XF\Entity\TemplateModification as ExtendedTemplateModificationEntity;

/**
 * Class TemplateModification
 *
 * @package TickTackk\DeveloperTools
 */
class TemplateModification extends XFCP_TemplateModification
{
    /**
     * @param ParameterBag $params
     *
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionTest(ParameterBag $params)
    {
        $response = parent::actionTest($params);

        if ($response instanceof View)
        {
            /** @var ExtendedTemplateModificationEntity $modification */
            $modification = $response->getParam('modification');

            if ($modification->Template->exists() && !$this->request->exists('style_id'))
            {
                $styleId = $modification->Template->style_id;
            }
            else
            {
                $styleId = $this->filter('style_id', 'uint');
            }

            if ($modification->type !== 'public')
            {
                return $response;
            }

            $style = $this->assertStyleExists($styleId);

            if ($params['modification_id'])
            {
                $modification = $this->assertTemplateModificationExists($params['modification_id']);
            }
            else
            {
                $modification = $this->em()->create('XF:TemplateModification');
            }

            $input = $this->filter([
                'type' => 'str',
                'template' => 'str',
                'modification_key' => 'str',
                'description' => 'str',
                'action' => 'str',
                'find' => 'str,no-trim',
                'replace' => 'str,no-trim',
                'execution_order' => 'uint',
                'enabled' => 'bool',
                'addon_id' => 'str'
            ]);

            $modification->bulkSet($input);
            $modification->preSave();

            $errors = $modification->getErrors();
            if (isset($errors['template']))
            {
                return $this->error($errors['template']);
            }
            if (isset($errors['find']))
            {
                return $this->error($errors['find']);
            }

            $templateForMasterStyle = $this->finder('XF:Template')
                ->where([
                    'style_id' => 0,
                    'title' => $input['template'],
                    'type' => $input['type']
                ])
                ->fetchOne();

            $templateForStyle = $this->finder('XF:Template')
                ->where([
                    'style_id' => $styleId,
                    'title' => $input['template'],
                    'type' => $input['type']
                ])
                ->fetchOne();

            // template
            if (!$templateForStyle && $templateForMasterStyle)
            {
                return $this->error(\XF::phrase('developerTools_requested_template_for_selected_style_could_not_be_found'));
            }

            if (!$templateForMasterStyle && !$templateForStyle)
            {
                return $this->error(\XF::phrase('requested_template_not_found'));
            }

            /** @noinspection PhpUndefinedFieldInspection */
            $content = $templateForStyle->template;

            $contentModified = $this->getTemplateModificationRepo()->applyTemplateModifications($content, [$modification]);

            $diff = new Diff();
            $diffs = $diff->findDifferences($content, $contentModified);

            $response->setParams([
                'modification' => $modification,
                'content' => $content,
                'contentModified' => $contentModified,
                'diffs' => $diffs,
                'style' => $style
            ]);
        }

        return $response;
    }


    /**
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionAutoComplete() : \XF\Mvc\Reply\View
    {
        $type = $this->filter('type', 'str');

        $response = parent::actionAutoComplete();
        if ($type !== 'public')
        {
            return $response;
        }

        $styleId = $this->filter('style_id', 'uint');
        $this->assertStyleExists($styleId);
        $q = $this->filter('q', 'str');

        $finderForStyle = $this->finder('XF:Template');
        $finderForStyle->where('type', $type)
            ->where('style_id', $styleId)
            ->where(
                $finderForStyle->columnUtf8('title'),
                'LIKE', $finderForStyle->escapeLike($q, '?%'))
            ->limit(10);

        $resultsForStyle = [];

        foreach ($finderForStyle->fetch() AS $templateMap)
        {
            $resultsForStyle[] = [
                'id' => $templateMap->title,
                'text' => $templateMap->title
            ];
        }

        if (!empty($resultsForStyle))
        {
            $response->setJsonParam('results', $resultsForStyle);
        }

        return $response;
    }

    /**
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionContents() : \XF\Mvc\Reply\View
    {
        $type = $this->filter('type', 'str');

        $response = parent::actionContents();
        if ($type !== 'public')
        {
            return $response;
        }

        $styleId = $this->filter('style_id', 'uint');
        $this->assertStyleExists($styleId);

        $templateTitle = $this->filter('template', 'str');

        /** @var \XF\Entity\Template $templateForStyle */
        if (
        $templateForStyle = $this->finder('XF:Template')
            ->where([
                'style_id' => $styleId,
                'title' => $templateTitle,
                'type' => $type
            ])
            ->fetchOne()
        )
        {
            $response->setJsonParam('template', $templateForStyle ? $templateForStyle->template : false);
        }

        return $response;
    }

    /**
     * @param ParameterBag $params
     *
     * @return Redirect|\XF\Mvc\Reply\Reroute
     */
    public function actionSave(ParameterBag $params)
    {
        $response = parent::actionSave($params);

        if ($response instanceof Redirect)
        {
            if ($params['modification_id'])
            {
                $modification = $this->assertTemplateModificationExists($params['modification_id']);
            }
            else
            {
                $modification = $this->assertTemplateModificationExists(Listener::$modificationId);
            }

            if ($this->request->exists('exit'))
            {
                $redirect = $this->buildLink('template-modifications', '', ['type' => $modification->type]);
            }
            else
            {
                $redirect = $this->buildLink('template-modifications/edit', $modification);
            }

            return $this->redirect($redirect);
        }

        return $response;
    }

    /**
     * @param \XF\Entity\TemplateModification $modification
     *
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function templateModificationAddEdit(\XF\Entity\TemplateModification $modification)
    {
        $response = parent::templateModificationAddEdit($modification);

        /** @var \XF\Entity\TemplateModification $_modification_ */
        $_modification = $response->getParam('modification');
        if ($_modification && $_modification->type === 'public')
        {
            if ($_modification->Template && !$this->request->exists('style_id'))
            {
                $styleId = $_modification->Template->style_id;
            }
            else
            {
                $styleId = $this->filter('style_id', 'uint');
            }

            $style = $this->assertStyleExists($styleId);
            $styleTree = $this->getStyleRepo()->getStyleTree();
            $response->setParam('styleTree', $styleTree);
            $response->setParam('style', $style);

            $modificationRouteParams = [
                'type' => $_modification->type,
            ];

            $modificationRouteType = 'add';

            if ($_modification->modification_id)
            {
                $modificationRouteType = 'edit/' . $_modification->modification_id;
                $modificationRouteParams = [];
            }

            $response->setParam('modificationRouteParams', $modificationRouteParams);
            $response->setParam('modificationRouteType', $modificationRouteType);
        }

        return $response;
    }

    /***
     * @param      $id
     * @param null $with
     * @param null $phraseKey
     *
     * @return \XF\Entity\Style|\XF\Mvc\Entity\Entity
     *
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertStyleExists($id, $with = null, $phraseKey = null)
    {
        if ($id === 0 || $id === '0')
        {
            return $this->getStyleRepo()->getMasterStyle();
        }

        return $this->assertRecordExists('XF:Style', $id, $with, $phraseKey);
    }

    /**
     * @return \XF\Repository\Style
     */
    protected function getStyleRepo() : \XF\Repository\Style
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('XF:Style');
    }
}