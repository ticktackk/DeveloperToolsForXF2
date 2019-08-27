<?php

namespace TickTackk\DeveloperTools\Test;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use TickTackk\DeveloperTools\Util\AddOn as AddOnUtil;
use XF\App as XFApp;
use XF\Entity\AddOn as AddOnEntity;
use XF\Entity\ClassExtension as ClassExtensionEntity;
use XF\Util\File as FileUtil;

/**
 * Class BaseTestCase
 *
 * @package TickTackk\DeveloperTools\Test
 *
 * @runClassInSeparateProcess
 */
abstract class BaseTestCase extends TestCase
{
    use PhpHelperTrait;

    const APP_TYPE_CLI = 'Cli';

    const APP_TYPE_ADMIN = 'Admin';

    const APP_TYPE_PUBLIC = 'Pub';

    const APP_TYPE_API = 'Api';

    /**
     * @var ArrayObject
     */
    private $containerBackup;

    /**
     * @var array
     */
    private $extensionsMap;

    /**
     * @return string
     */
    protected static function appType() : string
    {
        return self::APP_TYPE_PUBLIC;
    }

    public static function setUpBeforeClass(): void
    {
        self::setupApp();
    }

    protected static function setupApp() : void
    {
        /** @noinspection PhpIncludeInspection */
        require_once (dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'XF.php');
        require_once(__DIR__ . '/TestXF.php');

        TestXF::start(dirname(__DIR__, 5));
        static::runApp();
    }

    protected static function runApp() : void
    {
        self::setupInput();
        self::setupFiles();
        self::setupRequest();
        self::setupCookies();
        self::setupServer();

        $appType = static::appType();
        $appClass = "TickTackk\\DeveloperTools\\Test\\{$appType}\\App";

        TestXF::runApp($appClass);
    }

    /**
     * @return string
     */
    protected function getWorkingDirPath() : string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tck-xdt-' . crc32(static::class);
    }

    protected function setUp(): void
    {
        $this->setupWorkingDir();

        $this->app()->db()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->removeWorkingDir();

        $this->app()->db()->rollbackAll();
    }

    /**
     * @return XFApp
     */
    protected function app() : XFApp
    {
        return TestXF::app();
    }

    protected function setupWorkingDir() : void
    {
        $this->removeWorkingDir();

        FileUtil::createDirectory($this->getWorkingDirPath(), false);
    }

    protected function removeWorkingDir() : void
    {
        $workingDirPath = $this->getWorkingDirPath();

        if (file_exists($workingDirPath) && is_dir($workingDirPath))
        {
            FileUtil::deleteDirectory($workingDirPath);
        }
    }

    /**
     * @param string $file
     * @param string $content
     */
    protected function writeToFile(string $file, string $content) : void
    {
        FileUtil::writeFile($file, $content, false);
    }

    protected static function setupInput() : void
    {
    }

    protected static function setupFiles() : void
    {
    }

    protected static function setupRequest() : void
    {
    }

    protected static function setupServer() : void
    {
    }

    protected static function setupCookies() : void
    {
    }

    /**
     * @param bool $forwardSlash
     *
     * @return string
     */
    protected static function addOnId(bool $forwardSlash = false) : string
    {
        $addOnId = explode('\\Test\\', __CLASS__)[0];

        if ($forwardSlash)
        {
            $addOnId = str_replace('\\', '/', $addOnId);
        }

        return $addOnId;
    }

    /**
     * @param string|null $class
     * @param bool $realClass
     *
     * @return string
     */
    protected static function className(string $class = null, bool $realClass = true) : string
    {
        $class = $class ?? static::class;
        $addOnId = static::addOnId();

        $class =  str_replace($addOnId . '\Tests', $addOnId, $class);
        $class = substr($class, 0, strlen($class) - strlen('Test'));

        if ($realClass)
        {
            $app = TestXF::app();

            /** @var ClassExtensionEntity $classExtension */
            $classExtension = $app->finder('XF:ClassExtension')
                ->where('to_class', $class)
                ->fetchOne();

            if ($classExtension)
            {
                return $classExtension->from_class;
            }

            return $class;
        }

        return $class;
    }

    /**
     * @param string $formatter
     * @param string|null $className
     *
     * @return string
     */
    protected static function shortClassName(string $formatter, string $className = null) : string
    {
        return AddOnUtil::classToString($className ?: static::className(), $formatter);
    }
}