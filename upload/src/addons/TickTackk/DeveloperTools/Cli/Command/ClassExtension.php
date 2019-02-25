<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\Util\File;

/**
 * Class ClassExtension
 *
 * @package TickTackk\DeveloperTools\Cli\Command\AddOn
 */
class ClassExtension extends Command
{
	protected function configure() : void
	{
		$this
			->setName('ticktackk-devtools:create-class-extension')
			->setDescription('Creates an XF class-extension for an add-on and writes out a basic template file.')
			->setAliases(['tdt:create-class-extension', 'tdt:extend'])
            ->addArgument(
                'id',
                InputArgument::OPTIONAL,
                'Add-On ID'
            )
            ->addArgument(
                'class',
                InputArgument::OPTIONAL,
                'Class to extend'
            )
        ;
	}

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \XF\PrintableException
     */
	protected function execute(InputInterface $input, OutputInterface $output) : ? int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $addOnId = $input->getArgument('id');
        if (!$addOnId)
        {
            $question = new Question('<question>Enter the ID for the add-on:</question>');
            $question->setValidator($this->getAddOnQuestionFieldValidator('addon_id'));
            $addOnId = $helper->ask($input, $output, $question);
            $output->writeln('');
        }

        if (\XF::$versionId >= 2010000)
        {
            $addOnObj = new \XF\AddOn\AddOn($addOnId, \XF::app()->addOnManager());
        }
        else
        {
            $addOnObj = new \XF\AddOn\AddOn($addOnId);
        }

        $jsonPath = $addOnObj->getJsonPath();

        if (!file_exists($jsonPath))
        {
            $output->writeln('<error>The addon.json file must exist at {$jsonPath}.</error>');

            return 1;
        }

        $class = $input->getArgument('class');
        if (!$class)
        {
            $question = new Question('<question>Enter the class to extend:</question>');
            $class = $helper->ask($input, $output, $question);
            $output->writeln('');
        }
        $class = \str_replace(['//', '/', '_', '\\\\'], ['/', '\\', '\\', '\\'], $class);
        $class = trim($class);
        $fromClass = trim($class, '\\');
        $fromClassPath = \str_replace( '\\', DIRECTORY_SEPARATOR, $fromClass);
        $toClass = $addOnObj->prepareAddOnIdForClass() . '\\' . $fromClass;
        $toClassPath = $addOnObj->getAddOnDirectory() . DIRECTORY_SEPARATOR . $fromClassPath;
        $outputPath = $toClassPath . '.php';

        File::createDirectory(\dirname($toClassPath), false);

        if (!file_exists($outputPath))
        {
            $className = basename($fromClassPath);
            $namespace = \dirname(\str_replace( '\\', DIRECTORY_SEPARATOR, $toClass));
            $isEntity = basename($namespace) === 'Entity';
            $namespace = \str_replace(DIRECTORY_SEPARATOR, '\\', $namespace);

            $contents = '';
            $useStatements = '';

            if ($isEntity)
            {
                $useStatements =  <<<TEMPLATE
\n\nuse XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
TEMPLATE;
                $contents = <<<TEMPLATE
    /**
     * @param Structure \$structure
     *
     * @return Structure
     */
    public static function getStructure(Structure \$structure)
    {
        \$structure = parent::getStructure(\$structure);
    
        return \$structure;
    }
TEMPLATE;

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

            $written = File::writeFile($outputPath, $template, false);
            if ($written)
            {
                $output->writeln("Wrote class extension template to {$outputPath}");
            }
            else
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
	 * @param $key
	 *
	 * @return \Closure
	 */
	protected function getAddOnQuestionFieldValidator($key) : callable
	{
		return function($value) use($key)
		{
			$addOn = \XF::em()->create('XF:AddOn');

			$valid = $addOn->set($key, $value);
			if (!$valid)
			{
				$errors = $addOn->getErrors();
				if (isset($errors[$key]))
				{
					throw new \InvalidArgumentException($errors[$key]);
				}
			}
			return $value;
		};
	}
}
