<?php

namespace TickTackk\DeveloperTools\XF\Admin\View\AddOn;

use XF\Http\ResponseFile;
use XF\Mvc\View;

/**
 * Class Build
 *
 * @package TickTackk\DeveloperTools\XF\Admin\View\AddOn
 */
class Build extends View
{
    /**
     * @return ResponseFile
     */
    public function renderRaw() : ResponseFile
    {
        $this->response
            ->setDownloadFileName($this->params['fileName'])
            ->header('Content-type', 'application/x-zip')
            ->header('Content-Length', filesize($this->params['releasePath']))
            ->header('ETag', \XF::$time)
            ->header('X-Content-Type-Options', 'nosniff');

        return $this->response->responseFile($this->params['releasePath']);
    }
}