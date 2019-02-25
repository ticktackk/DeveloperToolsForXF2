<?php

namespace TickTackk\DeveloperTools\Admin\View\AddOn;

use XF\Mvc\View;

class Build extends View
{
	public function renderRaw()
	{
		$this->response
			->setDownloadFileName($this->params['fileName'])
			->header('Content-type', 'application/x-zip', true)
			->header('Content-Length', filesize($this->params['releasePath']), true)
			->header('ETag', \XF::$time)
			->header('X-Content-Type-Options', 'nosniff');

		return $this->response->responseFile($this->params['releasePath']);
	}
}