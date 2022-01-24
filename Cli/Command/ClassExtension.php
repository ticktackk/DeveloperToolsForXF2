<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\Cli\Command\AddOnActionTrait;
use XF\Util\File as FileUtil;
const USE_FUNCTION_PLACEHOLDER = true;
use function str_replace;

/**
 * @version 1.4.0
 */
class ClassExtension extends Command
{
    use AddOnActionTrait;

    protected function configure() : void
    {
        $this
            ->setName('tck-devtools:create-class-extension')
            ->setAliases(['tck-dt:create-class-extension'])
            ->setDescription('Creates an XF class-extension for an add-on and writes out a basic template file.')
            ->addArgument(
                'id',
                InputArgument::OPTIONAL,
                'Add-On ID'
            )
            ->addArgument(
                'class',
                InputArgument::OPTIONAL,
                'Class to extend'
            );
    }

    /**
     * @version 1.4.0
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \XF\PrintableException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $addOnId = $input->getArgument('id');
        if (!$addOnId)
        {
            $question = new Question('<question>Enter the ID for the add-on:</question>');
            $addOnId = $helper->ask($input, $output, $question);
            $output->writeln('');
        }

        $addOnObj = $this->checkEditableAddOn($addOnId, $error);
        if (!$addOnObj)
        {
            $output->writeln('<error>' . $error . '</error>');
            return 1;
        }

        $jsonPath = $addOnObj->getJsonPath();
        if (!file_exists($jsonPath))
        {
            $output->writeln(sprintf('<error>The addon.json file must exist at %s.</error>', $jsonPath));

            return 1;
        }

        $class = $input->getArgument('class');
        if (!$class)
        {
            $question = new Question('<question>Enter the class to extend:</question>');
            $class = $helper->ask($input, $output, $question);
            $output->writeln('');
        }
        $class = str_replace(['//', '/', '_', '\\\\'], ['/', '\\', '\\', '\\'], $class);
        $class = trim($class);
        $fromClass = trim($class, '\\');
        $fromClassPath = str_replace('\\', DIRECTORY_SEPARATOR, $fromClass);
        $toClass = $addOnObj->prepareAddOnIdForClass() . '\\' . $fromClass;
        $toClassPath = $addOnObj->getAddOnDirectory() . DIRECTORY_SEPARATOR . $fromClassPath;
        $outputPath = $toClassPath . '.php';

        FileUtil::createDirectory(dirname($toClassPath), false);

        if (!file_exists($outputPath))
        {
            $className = basename($fromClassPath);
            $namespace = dirname(str_replace('\\', DIRECTORY_SEPARATOR, $toClass));
            $isEntity = basename($namespace) === 'Entity';
            $isController = basename($namespace) === 'Controller';
            $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $namespace);

            $contents = '';
            $useStatements = $this->getCommonUseStatements();

            if ($isEntity)
            {
                $useStatements = <<<TEMPLATE


use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure as EntityStructure;
TEMPLATE;
                $contents = <<<TEMPLATE
    /**
     * @param EntityStructure \$structure
     *
     * @return EntityStructure
     */
    public static function getStructure(EntityStructure \$structure)
    {
        \$structure = parent::getStructure(\$structure);
    
        return \$structure;
    }
TEMPLATE;

            } else
            {
                if ($isController)
                {
                    $useStatements = <<<TEMPLATE


use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Mvc\Reply\Reroute as RerouteReply;
use XF\Mvc\Reply\Message as MessageReply;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Mvc\Reply\Error as ErrorReply;
TEMPLATE;

                }
            }

            $template = <<<TEMPLATE
<?php

namespace {$namespace};{$useStatements}

/**
 * Class {$className}
 * 
 * Extends \\{$fromClass}
 *
 * @package {$namespace}
 */
class {$className} extends XFCP_{$className}
{
{$contents}
}
TEMPLATE;

            $written = FileUtil::writeFile($outputPath, $template, false);
            if ($written)
            {
                $output->writeln("Wrote class extension template to {$outputPath}");
            } else
            {
                $output->writeln("Failed to write class extension template to {$outputPath}");
            }
        }


        $extension = \XF::app()->finder('XF:ClassExtension')
            ->where('from_class', '=', $fromClass)
            ->where('to_class', '=', $toClass)
            ->fetchOne();
        if (!$extension)
        {
            /** @var \XF\Entity\ClassExtension $extension */
            $extension = \XF::app()->em()->create('XF:ClassExtension');
            $extension->from_class = $fromClass;
            $extension->to_class = $toClass;
            $extension->execute_order = 10;
            $extension->active = true;
            $extension->addon_id = $addOnObj->getAddOnId();
            $extension->save();
        }

        return 0;
    }

    /**
     * @return string
     */
    protected function getCommonUseStatements() : string
    {
        return <<<TEMPLATE


use XF\App as BaseApp;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Service\AbstractService;
use XF\Mvc\Entity\Manager as EntityManager;
use XF\Job\Manager as JobManager;
TEMPLATE;

    }
}