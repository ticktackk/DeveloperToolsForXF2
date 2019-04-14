<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Diff;
use XF\Entity\TemplateModification;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

/**
 * Extends \XF\Admin\Controller\Template
 */
class Template extends XFCP_Template
{
    /**
     * @param ParameterBag $params
     *
     * @return \XF\Mvc\Reply\Error|View
     */
    public function actionEdit(ParameterBag $params)
    {
        $reply = parent::actionEdit($params);

        if ($reply instanceof View &&
            ($template = $reply->getParam('template')))
        {
            /** @var \XF\Entity\Template $template */
            $modifications = $this->finder('XF:TemplateModification')
                                  ->where([
                                              'type'     => $template->type,
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
     * @return View
     */
    public function actionViewModifications(ParameterBag $params) : View
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $masterTemplate = $this->assertTemplateExists($params->template_id);
        $style = $this->assertStyleExists($this->filter('style_id', 'uint'));

        $templateRepo = $this->getTemplateRepo();

        /** @var \XF\Entity\Template $template */
        $template = $templateRepo->findEffectiveTemplateInStyle($style, $masterTemplate->title, $masterTemplate->type)->fetchOne();

        $reload = $this->filter('reload', 'bool');
        $ids = null;
        if ($reload)
        {
            $ids = $this->filter('id', 'array-uint', []);
            $ids = \array_fill_keys($ids, true);
        }

        $status = null;

        /** @var \XF\Repository\TemplateModification $templateModRepo */
        $templateModRepo = $this->repository('XF:TemplateModification');
        $modifications = $this->finder('XF:TemplateModification')
                              ->where([
                                          'type'     => $template->type,
                                          'template' => $template->title,
                                      ])
                              ->whereAddOnActive()
                              ->order('execution_order')
                              ->fetch();

        $filtered = $modifications->filter(function (TemplateModification $mod) use ($ids) {
            if ($ids === null)
            {
                return $mod->enabled;
            }

            return isset($ids[$mod->modification_id]);
        });
        $filtered = $filtered->toArray();
        /** @var TemplateModification[] $modifications */
        $templateText = $templateModRepo->applyTemplateModifications($template->template, $filtered, $statuses);

        $diff = new Diff();
        $diffs = $diff->findDifferences($template->template, $templateText);

        $statuses = array_map(function($status)
        {
            if (is_numeric($status))
            {
                return 'Match count:' . $status;
            }
            return $status;
        }, $statuses);


        $viewParams = [
            'style'       => $style,
            'template'    => $template,
            'diffs'       => $diffs,
            'mods'        => $modifications->toArray(),
            'activeMods'  => $filtered,
            'status'      => $statuses,
            '_xfWithData' => $this->filter('_xfWithData', 'bool'),
        ];

        return $this->view('TickTackk\DeveloperTools:Template\Modifications\Compare', 'developerTools_template_modifications_compare', $viewParams);
    }
}
