<?php

namespace TickTackk\DeveloperTools\XF\Entity;

use TickTackk\DeveloperTools\XF\Repository\CodeEvent as ExtendedCodeEventRepo;
use XF\Mvc\Entity\Repository;
use XF\Repository\CodeEvent as CodeEventRepo;
use XF\Util\File as FileUtil;

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
        $eventId = $this->event_id;
        $callbackClass = $this->callback_class;
        $callbackMethod = $this->callback_method;
        $addOnId = $this->addon_id;

        if (!$this->exists() && $eventId && $callbackClass && $callbackMethod && $addOnId)
        {
            $addOn = $this->app()->addOnManager()->getById($addOnId);

            $addOnClass = $addOn->prepareAddOnIdForClass();
            $addOnDir = $addOn->getAddOnDirectory();

            $listenerPath = FileUtil::canonicalizePath('Listener.php', $addOnDir);
            if (!\file_exists($listenerPath))
            {
                \touch($listenerPath);
                $listenerContents = '';
            }
            else
            {
                $listenerContents = \trim(\file_get_contents($listenerPath));
            }

            $codeEventRepo = $this->getCodeEventRepo();
            $docBlock = $codeEventRepo->getDocBlockForCodeEvent($eventId, $callbackSignature);

            $callbackMethodWithBrackets = "{$callbackMethod}({$callbackSignature})";

            $returnType = '';
            $addOnJson = $addOn->getJson();
            $addOnRequirements = $addOnJson['require'] ?? [];
            if (\array_key_exists('php', $addOnRequirements))
            {
                $phpRequirements = $addOnRequirements['php'];
                if (\substr(\reset($phpRequirements), 0, 1) === '7')
                {
                    $returnType = ' : void';
                }
            }

            $methodBlock = <<<PHP
{$docBlock}
\tpublic static function {$callbackMethodWithBrackets}{$returnType}
\t{
\t}
PHP;

            if (\utf8_strlen($listenerContents) === 0)
            {
                $listenerContents = <<<PHP
<?php

namespace {$addOnClass};

/**
 * Class Listener
 * 
 * This class declares code event listeners for the add-on.
 * 
 * @package {$addOnClass}
 */
class Listener
{
{$methodBlock}
}
PHP;
            }
            else
            {
                if (!\preg_match('#\sstatic function ' . \preg_quote($callbackMethodWithBrackets) . '#si', $listenerContents))
                {
                    $listenerContents = \utf8_rtrim($listenerContents, "}");
                    $listenerContents .= \PHP_EOL . $methodBlock . \PHP_EOL . '}';
                }
            }

            if (utf8_strlen($listenerContents))
            {
                \file_put_contents($listenerPath, $listenerContents);
            }
        }

        parent::_preSave();
    }

    /**
     * Gets the XF:CodeEvent repository
     *
     * @return Repository|CodeEventRepo|ExtendedCodeEventRepo
     */
    protected function getCodeEventRepo()
    {
        return $this->repository('XF:CodeEvent');
    }
}