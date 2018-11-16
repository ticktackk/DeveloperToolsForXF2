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
    /**
     * @var AddOn
     */
    protected $addOn;

    /**
     * @var string
     */
    protected $addOnRoot;

    /**
     * @var string
     */
    protected $vendorDir;

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

        $this->addOnRoot = $this->getAddOn()->getAddOnDirectory();
        $this->vendorDir = $this->addOnRoot . DIRECTORY_SEPARATOR . 'vendor';
    }

    /**
     * @param AddOnEntity $addOn
     */
    public function setAddOn(AddOnEntity $addOn) : void
    {
        $this->addOn = new AddOn($addOn);
    }

    /**
     * @return AddOn
     */
    public function getAddOn() : AddOn
    {
        return $this->addOn;
    }

    /**
     * @param string $path
     * @param string $contentPath
     * @param bool $trim
     *
     * @return mixed|string
     */
    protected function cleanPath($path, $contentPath, $trim = true)
    {
        $sharedPath = utf8_substr($path, utf8_strlen(\XF::getRootDirectory()) + 1) . DIRECTORY_SEPARATOR . $contentPath;
        $cleanedPath = str_replace('\\', '/', $sharedPath);
        return $trim ? trim($cleanedPath, '/') : $cleanedPath;
    }

    /**
     * @param string $path
     * @param array $jsonContents
     *
     * @return array
     */
    protected function getNamespacesFromComposer($path, array $jsonContents) : array
    {
        if (empty($jsonContents['autoload']['psr-0']))
        {
            return [];
        }

        $namespaces = [];

        foreach ($jsonContents['autoload']['psr-0'] AS $namespace => $contentPath)
        {
            $namespaces[$namespace][] = $this->cleanPath($path, $contentPath);
        }

        return $namespaces;
    }

    /**
     * @param       $path
     * @param array $jsonContents
     * @param array $existingPsr4Arr
     *
     * @return array
     */
    protected function getPsr4FromComposer($path, array $jsonContents, array &$existingPsr4Arr = null) : array
    {
        if (empty($jsonContents['autoload']['psr-4']))
        {
            return [];
        }

        $psr4 = [];

        foreach ($jsonContents['autoload']['psr-4'] AS $namespace => $directoryList)
        {
            foreach ((array) $directoryList AS $contentPath)
            {
                $finalPath = $this->cleanPath($path, $contentPath);
                $psr4[$namespace][] = $finalPath;
                if ($existingPsr4Arr !== null)
                {
                    $existingPsr4Arr[$namespace][] = $finalPath;
                }
            }
        }

        return $psr4;
    }

    /**
     * @param string $path
     * @param array $jsonContents
     *
     * @return array
     */
    protected function getClassMapFromComposer($path, array $jsonContents) : array
    {
        if (empty($jsonContents['autoload']['classmap']))
        {
            return [];
        }

        $ds = DIRECTORY_SEPARATOR;

        $classMap = [];

        foreach ($jsonContents['autoload']['classmap'] AS $contentPath)
        {
            $src = rtrim($path . $ds . str_replace('/', $ds, $contentPath), $ds);

            if (is_dir($src))
            {
                $this->getClassMapsFromDir($src, $classMap);
            }
        }

        return $classMap;
    }

    /**
     * @param string $path
     * @param array $jsonContents
     *
     * @return array
     */
    protected function getRequireFilesFromComposer($path, array $jsonContents) : array
    {
        if (empty($jsonContents['autoload']['files']))
        {
            return [];
        }

        $requiredFiles = [];

        foreach ($jsonContents['autoload']['files'] AS $requiredFile)
        {
            $requiredFiles[] = $this->cleanPath($path, $requiredFile, false);
        }

        return $requiredFiles;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function exportData(array $data) : string
    {
        return var_export($data, true);
    }

    /**
     * @throws \XF\PrintableException
     */
    public function build() : void
    {
        $namespaces = [];
        $psr4 = [];
        $classMap = [];
        $requiredFiles = [];

        $files = $this->getFileIterator($this->vendorDir);
        foreach ($files AS $file)
        {
            if (!$file->isFile())
            {
                continue;
            }

            if ($file->getFilename() === 'composer.json')
            {
                $composerContents = json_decode(file_get_contents($file->getPathname()), true);

                $namespaces += $this->getNamespacesFromComposer($file->getPath(), $composerContents);
                $this->getPsr4FromComposer($file->getPath(), $composerContents, $psr4);
                $classMap += $this->getClassMapFromComposer($file->getPath(), $composerContents);
                $requiredFiles += $this->getRequireFilesFromComposer($file->getPath(), $composerContents);
            }
        }

        $this->createFakeComposer($namespaces, $psr4, $classMap, $requiredFiles);
    }

    /**
     * @param array $namespaces
     * @param array $psr4
     * @param array $classMap
     * @param array $requiredFiles
     *
     * @throws \XF\PrintableException
     */
    protected function createFakeComposer(array $namespaces, array $psr4, array $classMap, array $requiredFiles) : void
    {
        $addOn = $this->getAddOn();
        $fakeComposerPath = $this->addOnRoot . DIRECTORY_SEPARATOR . 'FakeComposer.php';

        $exportedNamespaces = $this->exportData($namespaces);
        $exportedPsr4 = $this->exportData($psr4);
        $exportedClassMap = $this->exportData($classMap);
        $exportedRequiredFiles = $this->exportData($requiredFiles);

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
        
        $xfRoot = \XF::getRootDirectory();

        foreach (self::getRequiredFiles() AS $filePath)
        {
            $_filePath = $xfRoot . DIRECTORY_SEPARATOR . $filePath;
            
            if (file_exists($_filePath) && is_readable($_filePath))
            {
                require $_filePath;
            }
            else
            {
                throw new \InvalidArgumentException("{$_filePath} does not exist.");
            }
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
    protected function getClassMapsFromDir($directory, array &$classMap) : void
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
                        while (isset($tokens[$index]) && \is_array($tokens[$index]))
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
                            $classMap[$namespace . (!empty($namespace) ? '\\' : '') . $tokens[$index][1]] = $this->cleanPath($file->getPath(), $file->getPathname());
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