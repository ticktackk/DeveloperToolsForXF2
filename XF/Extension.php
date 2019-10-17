<?php

namespace TickTackk\DeveloperTools\XF;

/**
 * Class Extension
 *
 * @package TickTackk\DeveloperTools\XF
 */
class Extension extends \XF\Extension
{
    /**
     * @param string $class
     * @param null   $fakeBaseClass
     *
     * @return string
     * @throws \Exception
     */
    public function extendClass($class, $fakeBaseClass = null)
    {
        $class = ltrim($class, '\\');

        if (isset($this->extensionMap[$class]))
        {
            return $this->extensionMap[$class];
        }

        if (!$class)
        {
            return $class;
        }

        $extensions = !empty($this->classExtensions[$class]) ? $this->classExtensions[$class] : [];
        if (!$extensions)
        {
            $this->extensionMap[$class] = $class;
            return $class;
        }

        if (!class_exists($class))
        {
            if ($fakeBaseClass)
            {
                $fakeBaseClass = ltrim($fakeBaseClass, '\\');
                class_alias($fakeBaseClass, $class);
            }
            else
            {
                $this->extensionMap[$class] = $class;
                return $class;
            }
        }

        $finalClass = $class;

        try
        {
            foreach ($extensions AS $extendClass)
            {
                if (preg_match('/[;,$\/#"\'\.()]/', $extendClass))
                {
                    continue;
                }

                // XFCP = XenForo Class Proxy, in case you're wondering

                $nsSplit = strrpos($extendClass, '\\');
                if ($nsSplit !== false && $ns = substr($extendClass, 0, $nsSplit))
                {
                    $proxyClass = $ns . '\\XFCP_' . substr($extendClass, $nsSplit + 1);
                }
                else
                {
                    $proxyClass = 'XFCP_' . $extendClass;
                }

                // TODO: there may be a situation where this fails. If we've changed the extensions after classes have
                // been loaded, it's possible these classes will already be loaded with a different config. Figure out
                // how to handle that if possible. Remains to be seen if it comes up (mostly relating to add-on imports).

                if (!class_exists($proxyClass))
                {
                    class_alias($finalClass, $proxyClass);
                }
                $finalClass = $extendClass;

                if (!class_exists($extendClass))
                {
                    throw new \Exception("Could not find class $extendClass when attempting to extend $class");
                }
            }
        }
        catch (\Exception $e)
        {
            $this->extensionMap[$class] = $class;
            throw $e;
        }

        $this->extensionMap[$class] = $finalClass;
        return $finalClass;
    }
}