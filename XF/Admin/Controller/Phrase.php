<?php

namespace TickTackk\DeveloperTools\XF\Admin\Controller;

use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Entity\Phrase as PhraseEntity;

use function array_key_exists, count;

/**
 * Class Phrase
 * 
 * Extends \XF\Admin\Controller\Phrase
 *
 * @package TickTackk\DeveloperTools\XF\Admin\Controller
 */
class Phrase extends XFCP_Phrase
{
    /**
     * Same as phraseSaveProcess just tweaked to receive phrase input
     *
     * @param PhraseEntity $phrase Phrase entity
     * @param array        $input  Phrase entity data.
     *
     * @return FormAction Returns FormAction on success
     */
    public function phraseSaveProcessWithInput(PhraseEntity $phrase, array $input) : FormAction
    {
        $form = $this->formAction();

        $form->setup(function () use ($phrase)
        {
            if ($phrase->language_id > 0)
            {
                $phrase->updateVersionId();
            }
        });

        $form->basicEntitySave($phrase, $input);

        return $form;
    }

    /**
     * @param ParameterBag $parameterBag ParameterBag object containing router related params
     *
     * @return RedirectReply Reply object. On success this will be redirect reply
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $parameterBag)
    {
        $db = $this->app()->db();
        $db->beginTransaction();

        $reply = parent::actionSave($parameterBag);

        /** @noinspection PhpUndefinedFieldInspection */
        if (!$parameterBag->phrase_id && $reply instanceof RedirectReply)
        {
            $phrases = $this->filter('phrases', 'array');
            if (count($phrases))
            {
                $languageId = $this->filter('language_id', 'uint');

                $phrases = array_map(function ($input) use ($languageId)
                {
                    $input = array_replace([
                        'title' => '',
                        'phrase_text' => '',
                        'global_cache' => false,
                        'addon_id' => '',
                        'language_id' => $languageId
                    ], $input);

                    if (array_key_exists('global_cache', $input))
                    {
                        $input['global_cache'] = $input['global_cache'] === '1';
                    }

                    return $input;
                }, $phrases);

                foreach ($phrases AS $input)
                {
                    if (empty($input['title']))
                    {
                        continue;
                    }

                    /** @var PhraseEntity $phrase */
                    $phrase = $this->em()->create('XF:Phrase');

                    $this->phraseSaveProcessWithInput($phrase, $input)->run();
                }

                $reply->setUrl($this->getDynamicRedirect());
            }
        }

        $db->commit();

        return $reply;
    }

    /**
     * Called to get new phrase block
     *
     * @return ViewReply Reply object. If no errors has occurred this will be an view reply
     * @throws ExceptionReply Thrown if response type is not JSON or language cannot be edited by the current visitor
     */
    public function actionMorePhraseBlock() : ViewReply
    {
        if ($this->responseType() !== 'json')
        {
            throw $this->exception($this->notFound());
        }
        $languageId = $this->filter('language_id', 'uint');

        $language = $this->assertLanguageExists($languageId);
        if (!$language->canEdit())
        {
            throw $this->exception($this->noPermission(\XF::phrase('phrases_in_this_language_can_not_be_modified')));
        }

        /** @var PhraseEntity $phrase */
        $phrase = $this->em()->create('XF:Phrase');
        $phrase->language_id = $language->language_id;

        $nextPhraseCount = $this->filter('current_phrase_count', 'uint') + 1;

        $viewParams = [
            'phrase' => $phrase,
            'language' => $language,
            'nextPhraseCount' => $nextPhraseCount
        ];
        return $this->view(
            'TickTackk\DeveloperTools\XF:Phrase\MorePhraseBlock',
            'tckDeveloperTools_phrase_more_block',
            $viewParams
        );
    }
}