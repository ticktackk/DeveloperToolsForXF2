<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Diff;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Redirect as RedirectReply;
use TickTackk\DeveloperTools\XF\Entity\TemplateModification as ExtendedTemplateModificationEntity;
use XF\Entity\TemplateModification as TemplateModificationEntity;
use XF\Repository\Style as StyleRepo;
use XF\Entity\Style as StyleEntity;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Mvc\Reply\Error as ErrorReply;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Reroute as RerouteReply;
use XF\Entity\Template as TemplateEntity;

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
     * @return ErrorReply|ViewReply
     * @throws ExceptionReply
     */
    public function actionTest(ParameterBag $params)
    {
        $response = parent::actionTest($params);

        if ($response instanceof ViewReply)
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
            } else
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

            $modification->bulkSet($input, ['forceSet' => true]);
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

            /** @var TemplateEntity $templateForMasterStyle */
            $templateForMasterStyle = $this->finder('XF:Template')
                ->where([
                    'style_id' => 0,
                    'title' => $input['template'],
                    'type' => $input['type']
                ])
                ->fetchOne();

            /** @var TemplateEntity $templateForStyle */
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
            ], false);
        }

        return $response;
    }


    /**
     * @return ViewReply
     * @throws ExceptionReply
     */
    public function actionAutoComplete() : ViewReply
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
     * @return ViewReply
     * @throws ExceptionReply
     */
    public function actionContents() : ViewReply
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

        /** @var TemplateEntity $templateForStyle */
        $templateForStyle = $this->finder('XF:Template')->where([
            'style_id' => $styleId,
            'title' => $templateTitle,
            'type' => $type
        ])->fetchOne();
        if ($templateForStyle)
        {
            $response->setJsonParam('template', $templateForStyle);
        }

        return $response;
    }

    /**
     * @var null|int
     */
    protected $lastInsertedTemplateModificationId;

    /**
     * @param TemplateModificationEntity $modification
     *
     * @return FormAction
     */
    protected function modificationSaveProcess(TemplateModificationEntity $modification)
    {
        $formAction = parent::modificationSaveProcess($modification);

        if ($formAction instanceof FormAction)
        {
            $formAction->complete(function () use ($modification)
            {
                $this->lastInsertedTemplateModificationId = $modification->getEntityId();
            });
        }

        return $formAction;
    }

    /**
     * @param ParameterBag $params
     *
     * @return RedirectReply|RerouteReply
     */
    public function actionSave(ParameterBag $params)
    {
        try
        {
            $response = parent::actionSave($params);

            if ($response instanceof RedirectReply)
            {
                if ($params['modification_id'])
                {
                    $modification = $this->assertTemplateModificationExists($params['modification_id']);
                } else
                {
                    $modification = $this->assertTemplateModificationExists($this->lastInsertedTemplateModificationId);
                }

                if ($this->request->exists('exit'))
                {
                    $redirect = $this->buildLink('template-modifications', '', ['type' => $modification->type]);
                } else
                {
                    $redirect = $this->buildLink('template-modifications/edit', $modification);
                }

                return $this->redirect($redirect);
            }

            return $response;
        }
        finally
        {
            $this->lastInsertedTemplateModificationId = null;
        }
    }

    /**
     * @param TemplateModificationEntity $modification
     *
     * @return ErrorReply|ViewReply
     * @throws ExceptionReply
     */
    protected function templateModificationAddEdit(TemplateModificationEntity $modification)
    {
        $response = parent::templateModificationAddEdit($modification);

        /** @var TemplateModificationEntity $_modification_ */
        $_modification = $response->getParam('modification');
        if ($_modification && $_modification->type === 'public')
        {
            if ($_modification->Template && !$this->request->exists('style_id'))
            {
                $styleId = $_modification->Template->style_id;
            } else
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

    /**
     * @param null|int    $id
     * @param array|null  $with
     * @param string|null $phraseKey
     *
     * @return StyleEntity|Entity
     * @throws ExceptionReply
     */
    protected function assertStyleExists(?int $id, array $with = null, string $phraseKey = null)
    {
        if ($id === 0)
        {
            return $this->getStyleRepo()->getMasterStyle();
        }

        return $this->assertRecordExists('XF:Style', $id, $with, $phraseKey);
    }

    /**
     * @return Repository|StyleRepo
     */
    protected function getStyleRepo() : StyleRepo
    {
        return $this->repository('XF:Style');
    }
}