<?php

namespace TickTackk\DeveloperTools\Service\FakeComposer;

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

        $iterator = $this->getFileIterator($vendorDir);

        $classes = [];
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
                            $classes[$namespace . '\\' . $tokens[$index][1]] = utf8_substr($file->getPathname(), utf8_strlen(\XF::getRootDirectory() . $ds));
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        $addOn = $this->getAddOn();

        $fakeComposerPath = $addOnRoot . $ds . 'FakeComposer.php';
        $exportedClasses = var_export($classes, true);
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
     * @param \XF\App $app
     */
    public static function appSetup(\XF\App $app)
    {
        \XF::$autoLoader->addClassMap(' . $exportedClasses . ');
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