<?php

namespace TickTackk\DeveloperTools\Service\Listener;

use Nette\PhpGenerator\PhpFile as NettePhpFile;
use TickTackk\DeveloperTools\Service\CodeEvent\DescriptionParser as CodeEventDescriptionParserSvc;
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

    protected $listenerNamespace;

    /**
     * @var null|string
     */
    protected $listenerClass;

    /**
     * @var null|string
     */
    protected $listenerMethod;

    /**
     * @var null|string
     */
    protected $listenerPath;

    /**
     * @var null|NettePhpFile
     */
    protected $listenerContents;

    public function __construct(
        BaseApp $app,
        CodeEventEntity $codeEvent,
        string $addOnId,
        ?string $listenerNamespace = null,
        ?string $listenerClass = null,
        ?string $listenerMethod = null
    )
    {
        $addOn = $app->addOnManager()->getById($addOnId);
        if ($addOn === null)
        {
            throw new InvalidAddOnIdProvidedException($addOnId);
        }

        $this->addOn = $addOn;
        $this->listenerNamespace = $listenerNamespace;
        $this->listenerClass = $listenerClass;
        $this->listenerMethod = $listenerMethod;
        $this->codeEvent = $codeEvent;

        parent::__construct($app);
    }

    protected function setup() : void
    {
        $this->setupListenerNamespaceClassMethod();;
        $this->setupListenerPath();
        $this->setupPhpGenerator();
    }

    protected function setupListenerNamespaceClassMethod() : void
    {
        if (is_null($this->getListenerNamespace()))
        {
            $this->listenerNamespace = $this->getAddOn()->prepareAddOnIdForClass();
        }

        if (is_null($this->getListenerClass()))
        {
            $this->listenerClass = 'Listener';
        }

        if (is_null($this->getListenerMethod()))
        {
            $this->listenerMethod = lcfirst(PhpUtil::camelCase($this->getCodeEvent()->event_id));
        }
    }

    protected function setupListenerPath() : void
    {
        $listenerClass = $this->getListenerNamespace() . '\\' . $this->getListenerClass();
        $this->listenerPath = realpath(\XF::$autoLoader->findFile($listenerClass));
    }

    protected function setupPhpGenerator() : void
    {
        $listenerPath = $this->getListenerPath();
        if (file_exists($listenerPath))
        {
            $fileContents = file_get_contents($listenerPath);
        }
        else
        {
            $fileContents = <<<PHP
<?php
PHP;
        }

        $listenerContents = NettePhpFile::fromCode($fileContents);

        $namespaces = $listenerContents->getNamespaces();
        $listenerNamespace = $this->getListenerNamespace();
        if (!count($namespaces) || !isset($namespacesp[$listenerNamespace]))
        {
            $listenerContents->addNamespace($listenerNamespace);
        }

        $listenerNamespaceObj = $listenerContents->getNamespaces()[$listenerNamespace];
        $classes = $listenerNamespaceObj->getClasses();
        $listenerClass = $this->getListenerClass();
        if (!count($classes) || !isset($classes[$listenerClass]))
        {
            $listenerNamespaceObj->addClass($listenerClass);
        }

        $this->listenerContents = $listenerContents;
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

        $this->setupPhpGenerator();
    }

    public function getListenerNamespace() :? string
    {
        return $this->listenerNamespace;
    }

    public function setListenerNamespace(?string $listenerNamespace) : void
    {
        $this->listenerNamespace = $listenerNamespace;

        $this->setupListenerPath();
        $this->setupPhpGenerator();
    }

    /**
     * @return string|null
     */
    public function getListenerClass() : ?string
    {
        return $this->listenerClass;
    }

    /**
     * @param string|null $listenerMethod
     */
    public function setListenerMethod(?string $listenerMethod) : void
    {
        $this->listenerMethod = $listenerMethod;

        $this->setupPhpGenerator();
    }

    /**
     * @return string|null
     */
    public function getListenerMethod(): ?string
    {
        return $this->listenerMethod;
    }

    public function getListenerPath() : string
    {
        return $this->listenerPath;
    }

    public function getListenerContents() : NettePhpFile
    {
        return $this->listenerContents;
    }

    public function create() : void
    {
        $listenerContents = $this->getListenerContents();
        $listenerNamespaceObj = $listenerContents->getNamespaces()[$this->getListenerNamespace()];
        $listenerClassObj = $listenerNamespaceObj->getClasses()[$this->getListenerClass()];
        $listenerMethod = $this->getListenerMethod();

        try
        {
           $listenerClassObj->getMethod($listenerMethod);
        }
        catch (\Nette\InvalidArgumentException $e)
        {
            $method = $listenerClassObj->addMethod($listenerMethod)->setPublic()->setStatic();
            $codeEvent = $this->getCodeEvent();

            /** @var CodeEventDescriptionParserSvc $descriptionParserSvc */
            $descriptionParserSvc = $this->service('TickTackk\DeveloperTools:CodeEvent\DescriptionParser', $codeEvent);
            $parsedDescription = $descriptionParserSvc->parse($this->getAddOn());

            if (!is_null($parsedDescription['returnType']))
            {
                $method->setReturnType($parsedDescription['returnType']);
            }

            foreach ($parsedDescription['arguments'] AS $argument)
            {
                $method->addParameter($argument['name'])
                    ->setType($argument['hint'])
                    ->setReference($argument['passedByRef']);
            }

            if (strlen($parsedDescription['description']))
            {
                $method->addComment($parsedDescription['description']);
            }

            if (strlen($parsedDescription['eventHint']))
            {
                if (strlen($parsedDescription['description']))
                {
                    $method->addComment('');
                }

                $method->addComment('Event hint: ' . $parsedDescription['eventHint']);
            }

            if (strlen($parsedDescription['description']) || strlen($parsedDescription['eventHint']))
            {
                $method->addComment('');
            }

            foreach ($parsedDescription['arguments'] AS $argument)
            {
                $comment = '@param';

                if ($argument['hint'])
                {
                    $comment .= ' ' . $argument['hint'];
                }

                $comment .= ' $' . $argument['name'];
                $comment .= ' ' . $argument['description'];

                $method->addComment($comment);
            }

            $method->addComment(PHP_EOL . '@noinspection PhpUnusedParameterInspection');
        }

        FileUtil::writeFile($this->getListenerPath(), (string) $listenerContents, false);
    }

    /**
     * @return BaseApp
     */
    protected function app() : BaseApp
    {
        return $this->app;
    }
}