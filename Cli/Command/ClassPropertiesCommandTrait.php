<?php

namespace TickTackk\DeveloperTools\Cli\Command;

use XF\App as BaseApp;
use XF\Entity\ClassExtension as ClassExtensionEntity;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Repository;
use XF\Repository\ClassExtension as ClassExtensionRepo;

trait ClassPropertiesCommandTrait
{
    /**
     * @param string $class
     * @param string $addOnId
     * @param array|null $requireAddonIds
     * @param array|null $softRequireAddonIds
     * @param string|null $subClassOf
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function getTypeHintForClass(
        string $class,
        string $addOnId,
        ?array $requireAddonIds,
        ?array $softRequireAddonIds,
        ?string $subClassOf = null
    ) : array
    {
        $classReflection = new \ReflectionClass($class);
        if (!$classReflection->isInstantiable())
        {
            return [];
        }

        $classes = [];
        if (is_string($subClassOf))
        {
            $classes[] = "\\$subClassOf";

            if (!$classReflection->isSubclassOf($subClassOf))
            {
                return $classes;
            }

            $classes[] = "\\$class";
        }

        $addOnIds = [$addOnId];

        if (is_array($requireAddonIds))
        {
            array_push($addOnIds, $requireAddonIds);
        }

        if (is_array($softRequireAddonIds))
        {
            array_push($addOnIds, $softRequireAddonIds);
        }

        /** @var AbstractCollection|ClassExtensionEntity[] $classExtensions */
        $classExtensions = $this->getClassExtensionRepo()->findExtensionsForList()
            ->where('from_class', '=', $class)
            ->where('addon_id', '=', $addOnIds)
            ->fetch();

        foreach ($classExtensions AS $classExtension)
        {
            $classes[] = $classExtension->to_class;
        }

        return array_unique($classes);
    }

    protected function app() : BaseApp
    {
        return \XF::app();
    }

    protected function repository(string $class) : Repository
    {
        return $this->app()->repository($class);
    }

    /**
     * @return Repository|ClassExtensionRepo
     */
    protected function getClassExtensionRepo() : ClassExtensionRepo
    {
        return $this->repository('XF:ClassExtension');
    }
}