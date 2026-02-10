<?php

namespace TickTackk\DeveloperTools\AdminSearch;

use XF\AdminSearch\AbstractHandler;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Router;

class TemplateModification extends AbstractHandler
{
	public function getDisplayOrder()
	{
		return 60;
	}

	public function search($text, $limit, array $previousMatchIds = [])
	{
		/** @var \XF\Finder\TemplateModification $finder */
		$finder = $this->app->finder('XF:TemplateModification');

		$conditions = [
			[$finder->caseInsensitive('description'), 'like', $finder->escapeLike($text, '%?%')],
			[$finder->caseInsensitive('modification_key'), 'like', $finder->escapeLike($text, '%?%')],
		];
		if ($previousMatchIds)
		{
			$conditions[] = ['modification_id', $previousMatchIds];
		}

		$finder
			->whereOr($conditions)
			->order($finder->caseInsensitive('modification_key'))
			->limit($limit);

		return $finder->fetch();
	}

	public function getTemplateData(Entity $record)
	{
		/** @var Router $router */
		$router = $this->app->container('router.admin');

		return [
			'link' => $router->buildLink('template-modification/edit', $record),
			'title' => $record->modification_key,
		];
	}

	public function isSearchable()
	{
		return \XF::$developmentMode;
	}
}
