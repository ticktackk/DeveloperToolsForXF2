<?php

namespace TickTackk\DeveloperTools\Service\FakeComposer;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use XF\AddOn\AddOn;
use XF\Entity\AddOn AS AddOnEntity;
use XF\Service\AbstractService;
use XF\Util\File;

/**
 * Class ClassMap
 *
 * @package TickTackk\DeveloperTools\XF\Service\FakeComposer
 */
class Creator extends AbstractService
{
    /** @var AddOn */
    protected $addOn;

    /**
     * ClassMap constructor.
     *
     * @param \XF\App $app
     * @param AddOnEntity   $addOn
     */
    public function __construct(\XF\App $app, AddOnEntity $addOn)
    {
        parent::__construct($app);
        $this->setAddOn($addOn);
    }

    /**
     * @param AddOnEntity $addOn
     */
    public function setAddOn(AddOnEntity $addOn)
    {
        $this->addOn = new AddOn($addOn);
    }

    /**
     * @return AddOn
     */
    public function getAddOn()
    {
        return $this->addOn;
    }

    /**
     * @throws \XF\PrintableException
     */
    public function build()
    {
        $ds = DIRECTORY_SEPARATOR;
        $addOnRoot = $this->getAddOn()->getAddOnDirectory();
        $vendorDir = $addOnRoot . $ds . 'vendor';

        $namespaces = [];
        $psr4 = [];
        $classMap = [];
        $requiredFiles = [];

        $local = new Local($vendorDir);
        $localFs = new Filesystem($local);

        $cleanPath = function ($path, $contentPath, $trim = true) use($vendorDir, $ds)
        {
            $cleanedPath = str_replace('\\', '/', utf8_substr($vendorDir, utf8_strlen(\XF::getRootDirectory() . $ds)) . '/' . dirname($path) . '/' . $contentPath);
            return $trim ? trim($cleanedPath, '/') : $cleanedPath;
        };

        foreach ($localFs->listContents('', true) AS $info)
        {
            if ($info['type'] === 'file' && $info['basename'] === 'composer.json')
            {
                $composerContents = json_decode(file_get_contents(
                    $vendorDir . $ds . $info['path']
                ), true);

                if (!empty($composerContents['autoload']['psr-0']))
                {
                    foreach ($composerContents['autoload']['psr-0'] AS $namespace => $contentPath)
                    {
                        $namespaces[$namespace][] = $cleanPath($info['path'], $contentPath);
                    }
                }

                if (!empty($composerContents['autoload']['psr-4']))
                {
                    foreach ($composerContents['autoload']['psr-4'] AS $namespace => $directoryList)
                    {
                        foreach ((array) $directoryList AS $contentPath)
                        {
                            $psr4[$namespace][] = $cleanPath($info['path'], $contentPath);
                        }
                    }
                }

                if (!empty($composerContents['autoload']['classmap']))
                {
                    foreach ($composerContents['autoload']['classmap'] AS $contentPath)
                    {
                        $src = rtrim($vendorDir . $ds . dirname($info['path']) . $ds . str_replace('/', $ds, $contentPath), $ds);
                        if (is_dir($src))
                        {
                            $this->getClassMapsFromDir($src, $classMap);
                        }
                    }
                }

                if (!empty($composerContents['autoload']['files']))
                {
                    foreach ($composerContents['autoload']['files'] AS $file)
                    {
                        $requiredFiles[] = $cleanPath($info['path'], $file, false);
                    }
                }
            }
        }

        $addOn = $this->getAddOn();

        $fakeComposerPath = $addOnRoot . $ds . 'FakeComposer.php';

        $exportedNamespaces = var_export($namespaces, true);
        $exportedPsr4 = var_export($psr4, true);
        $exportedClassMap = var_export($classMap, true);
        $exportedRequiredFiles = var_export($requiredFiles, true);

        $fakeComposerContent = '<?php

// ################## THIS IS A GENERATED FILE ##################
// #################### DO NOT EDIT DIRECTLY ####################

namespace ' . $addOn->prepareAddOnIdForClass() . ';

/**
 * Class FakeComposer
 *
 * @package ' . $addOn->prepareAddOnIdForClass() . '
 */
class FakeComposer
{
    /**
     * @return array
     */
    protected static function getNamespaces()
    {
        /** @noinspection PhpTraditionalSyntaxArrayLiteralInspection */
        return ' . $exportedNamespaces . ';
    }

    /**
     * @return array
     */
    protected static function getPsr4()
    {
        /** @noinspection PhpTraditionalSyntaxArrayLiteralInspection */
        return ' . $exportedPsr4 . ';
    }

    /**
     * @return array
     */
    protected static function getClassMap()
    {
        /** @noinspection PhpTraditionalSyntaxArrayLiteralInspection */
        return ' . $exportedClassMap . ';
    }

    /**
     * @return array
     */
    protected static function getRequiredFiles()
    {
        /** @noinspection PhpTraditionalSyntaxArrayLiteralInspection */
        return ' . $exportedRequiredFiles . ';
    }
    
    /**
     * @param \XF\App $app
     */
    public static function appSetup(\XF\App $app)
    {
        foreach (self::getNamespaces() AS $namespace => $filePath)
        {
            \XF::$autoLoader->add($namespace, $filePath);
        }

        foreach (self::getPsr4() AS $namespace => $filePath)
        {
            \XF::$autoLoader->addPsr4($namespace, $filePath, true);
        }

        \XF::$autoLoader->addClassMap(self::getClassMap());

        foreach (self::getRequiredFiles() AS $filePath)
        {
            require $filePath;
        }
    }
}';

        File::writeFile($fakeComposerPath, $fakeComposerContent, false);

        $fakeComposerCELExists = $this->finder('XF:CodeEventListener')
            ->where('callback_class', $addOn->prepareAddOnIdForClass() . '\\FakeComposer')
            ->where('callback_method', 'appSetup')
            ->where('addon_id', $addOn->getAddOnId())
            ->fetchOne();

        if (!$fakeComposerCELExists)
        {
            /** @var \XF\Entity\CodeEventListener $fakeComposerCEL */
            $fakeComposerCEL = $this->em()->create('XF:CodeEventListener');
            $fakeComposerCEL->event_id = 'app_setup';
            $fakeComposerCEL->callback_class = $addOn->prepareAddOnIdForClass() . '\\FakeComposer';
            $fakeComposerCEL->callback_method = 'appSetup';
            $fakeComposerCEL->addon_id = $addOn->getAddOnId();
            $fakeComposerCEL->description = 'Loads packages from vendor directory';
            $fakeComposerCEL->save();
        }
    }

    /**
     * @param       $directory
     * @param array $classMap
     */
    protected function getClassMapsFromDir($directory, array &$classMap)
    {
        $iterator = $this->getFileIterator($directory);

        foreach ($iterator AS $file)
        {
            if ($file->isDir())
            {
                continue;
            }

            if ($file->getExtension() !== 'php')
            {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $tokens = token_get_all($content);
            $namespace = '';
            for ($index = 0; isset($tokens[$index]); $index++)
            {
                if (!isset($tokens[$index][0]))
                {
                    continue;
                }

                switch ($tokens[$index][0])
                {
                    case T_NAMESPACE:
                        $index += 2;
                        while (isset($tokens[$index]) && is_array($tokens[$index]))
                        {
                            $namespace .= $tokens[$index++][1];
                        }
                        break;

                    case T_CLASS:
                    case T_INTERFACE:
                    case T_TRAIT:
                        if (T_WHITESPACE === $tokens[$index + 1][0] && T_STRING === $tokens[$index + 2][0])
                        {
                            $index += 2;
                            $classMap[$namespace . (!empty($namespace) ? '\\' : '') . $tokens[$index][1]] = utf8_substr($file->getPathname(), utf8_strlen(\XF::getRootDirectory() . DIRECTORY_SEPARATOR));
                        }
                        break;

                    default:
                        break;
                }
            }
        }
    }

    /**
     * @param $path
     *
     * @return \SplFileInfo[]|\RecursiveIteratorIterator
     */
    protected function getFileIterator($path)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path, \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }
}