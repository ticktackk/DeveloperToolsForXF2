<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Diff;
use XF\Entity\TemplateModification as TemplateModificationEntity;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Error as ErrorReply;
use XF\Entity\Template as TemplateEntity;
use XF\Repository\TemplateModification as TemplateModificationRepo;

/**
 * Extends \XF\Admin\Controller\Template
 */
class Template extends XFCP_Template
{
    /**
     * @param ParameterBag $params
     *
     * @return ErrorReply|ViewReply
     */
    public function actionEdit(ParameterBag $params)
    {
        $reply = parent::actionEdit($params);

        $addModificationCount = true;
        $addOns = \XF::app()->container('addon.cache');
        if (isset($addOns['SV/StandardLib']) && $addOns['SV/StandardLib'] >= 1050000)
        {
            $addModificationCount = false;
        }

        /** @var TemplateEntity $template */
        $template = $reply->getParam('template');
        if ($addModificationCount && $reply instanceof ViewReply && $template)
        {
            $modifications = $this->finder('XF:TemplateModification')
                ->where([
                    'type' => $template->type,
                    'template' => $template->title,
                    //'enabled'  => 1
                ])
                ->whereAddOnActive()
                ->order('execution_order')
                ->total();

            $reply->setParam('modificationCount', $modifications);
        }

        return $reply;
    }

    /**
     * @param ParameterBag $params
     *
     * @return ViewReply
     */
    public function actionViewModifications(ParameterBag $params) : ViewReply
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $masterTemplate = $this->assertTemplateExists($params->template_id);
        $style = $this->assertStyleExists($this->filter('style_id', 'uint'));

        $templateRepo = $this->getTemplateRepo();

        /** @var TemplateEntity $template */
        $template = $templateRepo->findEffectiveTemplateInStyle($style, $masterTemplate->title, $masterTemplate->type)->fetchOne();

        $reload = $this->filter('reload', 'bool');
        $ids = null;
        if ($reload)
        {
            $ids = $this->filter('id', 'array-uint', []);
            $ids = array_fill_keys($ids, true);
        }

        $status = null;

        /** @var TemplateModificationRepo $templateModRepo */
        $templateModRepo = $this->repository('XF:TemplateModification');

        /** @var TemplateModificationEntity[]|AbstractCollection $modifications */
        $modifications = $this->finder('XF:TemplateModification')
            ->where([
                'type' => $template->type,
                'template' => $template->title,
            ])
            ->whereAddOnActive()
            ->order('execution_order')
            ->fetch();

        $filtered = $modifications->filter(function (TemplateModificationEntity $mod) use ($ids)
        {
            if ($ids === null)
            {
                return $mod->enabled;
            }

            return isset($ids[$mod->modification_id]);
        });
        $filtered = $filtered->toArray();
        $templateText = $templateModRepo->applyTemplateModifications($template->template, $filtered, $statuses);

        $diff = new Diff();
        $diffs = $diff->findDifferences($template->template, $templateText);

        $statuses = array_map(function ($status)
        {
            if (is_numeric($status))
            {
                return \XF::phrase('tckDeveloperTools_match_count_x', [
                    'count' => $this->app()->language()->numberFormat($status)
                ]);
            }
            return $status;
        }, $statuses);

        $viewParams = [
            'style' => $style,
            'template' => $template,
            'diffs' => $diffs,
            'mods' => $modifications->toArray(),
            'activeMods' => $filtered,
            'status' => $statuses,
            '_xfWithData' => $this->filter('_xfWithData', 'bool'),
        ];
        return $this->view(
            'TickTackk\DeveloperTools:Template\Modifications\Compare',
            'tckDeveloperTools_template_modifications_compare',
            $viewParams
        );
    }
}