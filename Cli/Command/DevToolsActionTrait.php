<?php

namespace TickTackk\DeveloperTools\Cli\Command;

/**
 * Trait DevToolsActionTrait
 *
 * @package TickTackk\DeveloperTools\Cli\Command
 */
trait DevToolsActionTrait
{
	/**
	 * @param \XF\AddOn\AddOn $addOn
	 *
	 * @return string
	 */
	public function getAddOnRepoDir(\XF\AddOn\AddOn $addOn) : string
	{
		$addOnDirectory = $addOn->getAddOnDirectory();
		$ds = DIRECTORY_SEPARATOR;
		
		$repoRoot = $addOnDirectory . $ds . '_repo';
		
		/** @var \TickTackk\DeveloperTools\XF\Entity\AddOn $addOnEntity */
		$addOnEntity = $addOn->getInstalledAddOn();
		
		$gitConfigurations = $addOnEntity->GitConfigurations;
		if (!empty($gitConfigurations['custom_repo']))
		{
			$repoRoot = preg_replace_callback('/({([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)})/', function ($match) use ($addOn)
			{
				$placeholder = $match[1];
				$property = $match[2];
				
				$value = $addOn->{$property};
				
				if (!$value || !is_scalar($value))
				{
					return $placeholder;
				}
				
				return $value;
			}, $gitConfigurations['custom_repo']);
			
			if (utf8_substr($repoRoot, 0, -1) !== '/')
			{
				$repoRoot = \XF\Util\File::canonicalizePath($repoRoot);
			}
		}
		
		return $repoRoot;
	}
}