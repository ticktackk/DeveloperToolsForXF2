<?php

namespace TickTackk\DeveloperTools\Service\Listener;

use TickTackk\DeveloperTools\Service\CodeEvent\DescriptionParser as CodeEventDescriptionParserSvc;
use TickTackk\DeveloperTools\Service\CodeEvent\DocBlockGenerator as CodeEventDocBlockGeneratorSvc;
use TickTackk\DeveloperTools\Service\CodeEvent\SignatureGenerator as CodeEventSignatureGeneratorSvc;
use TickTackk\DeveloperTools\Service\Listener\Exception\InvalidAddOnIdProvidedException;
use XF\App as BaseApp;
use XF\Entity\CodeEvent as CodeEventEntity;
use XF\Service\AbstractService;
use XF\AddOn\AddOn;
use XF\Util\File as FileUtil;
use XF\Util\Php as PhpUtil;

use function strlen;

/**
 * Class
 *
 * @package TickTackk\DeveloperTools\Service\Listener
 */
class Creator extends AbstractService
{
    /**
     * @var CodeEventEntity
     */
    protected $codeEvent;

    /**
     * @var AddOn
     */
    protected $addOn;

    /**
     * @var null|string
     */
    protected $listenerClass;

    /**
     * @var null|string
     */
    protected $listenerMethod;

    /**
     * Creator constructor.
     *
     * @param BaseApp $app
     * @param CodeEventEntity $codeEvent
     * @param string $addOnId
     */
    public function __construct(BaseApp $app, CodeEventEntity $codeEvent, string $addOnId)
    {
        parent::__construct($app);

        $this->codeEvent = $codeEvent;

        $addOn = $this->app()->addOnManager()->getById($addOnId);
        if ($addOn === null)
        {
            throw new InvalidAddOnIdProvidedException($addOnId);
        }
        $this->addOn = $addOn;
    }

    /**
     * @return CodeEventEntity
     */
    public function getCodeEvent() : CodeEventEntity
    {
        return $this->codeEvent;
    }

    /**
     * @return AddOn
     */
    public function getAddOn() : AddOn
    {
        return $this->addOn;
    }

    /**
     * @param string|null $listenerClass
     */
    public function setListenerClass(?string $listenerClass) : void
    {
        $this->listenerClass = $listenerClass;
    }

    /**
     * @return string|null
     */
    public function getListenerClass() : ?string
    {
        return $this->listenerClass;
    }

    /**
     * @return string
     */
    public function getFallbackListenerClass() : string
    {
        return $this->getAddOn()->prepareAddOnIdForClass() . '\\Listener';
    }

    /**
     * @param string $listenerClass
     *
     * @return string
     */
    public function getListenerPath(string $listenerClass) : string
    {
        $addOn = $this->getAddOn();
        $addOnRoot = $addOn->getAddOnDirectory();
        $listenerFileName = str_replace('\\', \XF::$DS, substr($listenerClass, strlen($addOn->getAddOnId()) + 1));

        return FileUtil::canonicalizePath($listenerFileName, $addOnRoot) . '.php';
    }

    /**
     * @param string|null $listenerMethod
     */
    public function setListenerMethod(?string $listenerMethod) : void
    {
        $this->listenerMethod = $listenerMethod;
    }

    /**
     * @return string|null
     */
    public function getListenerMethod(): ?string
    {
        return $this->listenerMethod;
    }

    /**
     * @return string
     */
    public function getFallbackListenerMethod() : string
    {
        return lcfirst(PhpUtil::camelCase($this->getCodeEvent()->event_id));
    }

    /**
     * @param string $listenerClass
     * @param string $listenerNamespace
     *
     * @return string
     */
    public function getFallbackListenerContents(string $listenerClass, string $listenerNamespace) : string
    {
        return <<<PHP
<?php

namespace {$listenerNamespace};

/**
* Class {$listenerClass}
 * 
 * @package {$listenerNamespace}
 */
class {$listenerClass}
{
}
PHP;
    }

    public function getListenerMethodBlock
    (
        /** @noinspection PhpUnusedParameterInspection */
        string $listenerMethod,
        string $indent = null
    ) : string
    {
        $codeEvent = $this->getCodeEvent();

        /** @var CodeEventDescriptionParserSvc $descriptionParserSvc */
        $descriptionParserSvc = $this->service('TickTackk\DeveloperTools:CodeEvent\DescriptionParser', $codeEvent);
        $parsedDescription = $descriptionParserSvc->parse();

        /** @var CodeEventDocBlockGeneratorSvc $docBlockGeneratorSvc */
        $docBlockGeneratorSvc = $this->service('TickTackk\DeveloperTools:CodeEvent\DocBlockGenerator', $parsedDescription);
        $docBlock = $docBlockGeneratorSvc->generate();

        /** @var CodeEventSignatureGeneratorSvc $signatureGeneratorSvc */
        $signatureGeneratorSvc = $this->service('TickTackk\DeveloperTools:CodeEvent\SignatureGenerator', $parsedDescription);
        $signature = $signatureGeneratorSvc->generate();

        $listenerMethodBlock = <<<PHP
{$docBlock}
    public static function {$listenerMethod}($signature)
    {
        
    }
PHP;

        return $listenerMethodBlock . PHP_EOL;
    }

    public function create() : void
    {
        $listenerFullClass = $this->getListenerClass() ?: $this->getFallbackListenerClass();
        $listenerNamespace = substr($listenerFullClass, 0, strrpos($listenerFullClass, '\\'));
        $listenerClass = substr($listenerFullClass, strlen($listenerNamespace) + 1);
        $listenerMethod = $this->getListenerMethod() ?: $this->getFallbackListenerMethod();
        $listenerPath = $this->getListenerPath($listenerFullClass);

        $listenerContents = file_exists($listenerPath)
            ? file_get_contents($listenerPath)
            : $this->getFallbackListenerContents($listenerClass, $listenerNamespace);
        $listenerContents = utf8_trim($listenerContents);

        if (!method_exists($listenerFullClass, $listenerMethod))
        {
            $listenerContents = utf8_substr($listenerContents, 0, utf8_strlen($listenerContents) - 1);
            $listenerContents .= $this->getListenerMethodBlock($listenerMethod);
            $listenerContents .= PHP_EOL .'}';
        }

        file_put_contents($listenerPath, $listenerContents);
    }

    /**
     * @return BaseApp
     */
    protected function app() : BaseApp
    {
        return $this->app;
    }
}