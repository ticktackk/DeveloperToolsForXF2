<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Mvc\ParameterBag;
use XF\Diff;

class TemplateModification extends XFCP_TemplateModification
{
    protected function templateModificationAddEdit(\XF\Entity\TemplateModification $modification)
    {
        $response = parent::templateModificationAddEdit($modification);

        /** @var \XF\Entity\TemplateModification $_modification_ */
        if ($_modification = $response->getParam('modification'))
        {
            if ($_modification->type == 'public')
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
        }

        return $response;
    }

    public function actionTest(ParameterBag $params)
    {
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

        $content = $templateForMasterStyle->template;
        $style = null;

        if ($modification->type == 'public')
        {
            if ($modification->Template->exists() && !$this->request->exists('style_id'))
            {
                $styleId = $modification->Template->style_id;
            }
            else
            {
                $styleId = $this->filter('style_id', 'uint');
            }
            $style = $this->assertStyleExists($styleId);

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
            elseif (!$templateForMasterStyle && !$templateForStyle)
            {
                return $this->error(\XF::phrase('requested_template_not_found'));
            }
            $content = $templateForStyle->template;
        }

        $contentModified = $this->getTemplateModificationRepo()->applyTemplateModifications($content, [$modification]);

        $diff = new Diff();
        $diffs = $diff->findDifferences($content, $contentModified);

        $viewParams = [
            'modification' => $modification,
            'content' => $content,
            'contentModified' => $contentModified,
            'diffs' => $diffs,
            'style' => $style
        ];

        return $this->view('XF:TemplateModification\Test', 'template_modification_test', $viewParams);
    }

    public function actionAutoComplete()
    {
        $type = $this->filter('type', 'str');

        $types = $this->getTemplateModificationRepo()->getModificationTypes();
        if (empty($types[$type]))
        {
            $type = 'public';
        }

        $styleId = 0;
        if ($type == 'public')
        {
            $styleId = $this->filter('style_id', 'uint');
            $this->assertStyleExists($styleId);
        }

        $q = $this->filter('q', 'str');

        $finderForMasterStyle = $this->finder('XF:Template');
        $finderForMasterStyle->where('type', $type)
            ->where('style_id', 0)
            ->where(
                $finderForMasterStyle->columnUtf8('title'),
                'LIKE', $finderForMasterStyle->escapeLike($q, '?%'))
            ->limit(10);

        $resultsForMasterStyle = [];
        foreach ($finderForMasterStyle->fetch() AS $templateMap)
        {
            $resultsForMasterStyle[] = [
                'id' => $templateMap->title,
                'text' => $templateMap->title
            ];
        }

        $finalResults = $resultsForMasterStyle;
        if ($type == 'public')
        {
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

            if (count($resultsForStyle))
            {
                $finalResults = $resultsForStyle;
            }
        }

        $view = $this->view();
        $view->setJsonParam('results', $finalResults);

        return $view;
    }

    public function actionContents()
    {
        $type = $this->filter('type', 'str');

        $types = $this->getTemplateModificationRepo()->getModificationTypes();
        if (empty($types[$type]))
        {
            $type = 'public';
        }

        $styleId = 0;
        if ($type == 'public')
        {
            $styleId = $this->filter('style_id', 'uint');
            $this->assertStyleExists($styleId);
        }

        $templateTitle = $this->filter('template', 'str');

        /** @var \XF\Entity\Template $templateForMasterStyle */
        $templateForMasterStyle = $this->finder('XF:Template')
            ->where([
                'style_id' => 0,
                'title' => $templateTitle,
                'type' => $type
            ])
            ->fetchOne();

        $finalTemplate = $templateForMasterStyle;

        if ($type == 'public')
        {
            /** @var \XF\Entity\Template $templateForStyle */
            $templateForStyle = $this->finder('XF:Template')
                ->where([
                    'style_id' => $styleId,
                    'title' => $templateTitle,
                    'type' => $type
                ])
                ->fetchOne();

            if ($templateForStyle)
            {
                $finalTemplate = $templateForStyle;
            }
        }

        $view = $this->view('XF:TemplateModification\Contents', '');
        $view->setJsonParam('template', $finalTemplate ? $finalTemplate->template : false);
        return $view;
    }

    /**
     * @return \XF\Repository\Style
     */
    protected function getStyleRepo()
    {
        return $this->repository('XF:Style');
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return \XF\Entity\Style
     */
    protected function assertStyleExists($id, $with = null, $phraseKey = null)
    {
        if ($id === 0 || $id === "0")
        {
            return $this->getStyleRepo()->getMasterStyle();
        }

        return $this->assertRecordExists('XF:Style', $id, $with, $phraseKey);
    }
}