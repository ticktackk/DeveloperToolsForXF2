<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use TickTackk\DeveloperTools\Service\Listener\Creator as ListenerCreatorSvc;
use TickTackk\DeveloperTools\XF\Repository\CodeEvent as ExtendedCodeEventRepo;
use XF\Entity\CodeEvent as CodeEventEntity;
use XF\Mvc\Entity\Repository;
use XF\Repository\CodeEvent as CodeEventRepo;

use function array_key_exists;

/**
 * Class CodeEventListener
 * 
 * Extends \XF\Entity\CodeEventListener
 *
 * @package TickTackk\DeveloperTools\XF\Entity
 */
class CodeEventListener extends XFCP_CodeEventListener
{
    protected function _preSave()
    {
        parent::_preSave();

        if (array_key_exists('callback_method', $this->getErrors()))
        {
            unset($this->_errors['callback_method']);

            $eventId = $this->event_id;
            $callbackClass = $this->callback_class;
            $callbackMethod = $this->callback_method;
            $addOnId = $this->addon_id;

            if ($eventId && strlen($callbackClass) && strlen($callbackMethod) && strlen($addOnId))
            {
                /** @var CodeEventEntity $codeEvent */
                $codeEvent = $this->em()->find('XF:CodeEvent', $eventId);
                if (!$codeEvent)
                {
                    parent::_preSave();
                    return;
                }

                /** @var ListenerCreatorSvc $listenerCreatorSvc */
                $listenerCreatorSvc = $this->app()->service(
                    'TickTackk\DeveloperTools:Listener\Creator',
                    $codeEvent, $this->addon_id
                );

                $callbackClassParts = explode('\\', $callbackClass);
                $callbackClassPartsIndex = count($callbackClassParts) - 1;
                $callbackClass = $callbackClassParts[$callbackClassPartsIndex];
                unset($callbackClassParts[$callbackClassPartsIndex]);
                $callbackNamespace = implode('\\', $callbackClassParts);

                $listenerCreatorSvc->setListenerNamespace($callbackNamespace);
                $listenerCreatorSvc->setListenerClass($callbackClass);
                $listenerCreatorSvc->setListenerMethod($callbackMethod);
                $listenerCreatorSvc->create();
            }
        }
    }

    /**
     * Gets the XF:CodeEvent repository
     *
     * @return Repository|CodeEventRepo|ExtendedCodeEventRepo
     */
    protected function getCodeEventRepo() : CodeEventRepo
    {
        return $this->repository('XF:CodeEvent');
    }
}