<?php

namespace TickTackk\DeveloperTools\XF\DevelopmentOutput;

use TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait;
use XF\Mvc\Entity\Entity;

class Phrase extends XFCP_Phrase
{
    use CommitTrait;

    /**
     * @param \XF\Entity\Phrase $entity
     *
     * @return array
     */
    public function getOutputCommitData(Entity $phrase)
    {
        return [
            'phrase_id',
            'phrase_text',
            'title'
        ];
    }
}