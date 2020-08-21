<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;

final class NullLoader implements LoaderInterface
{
    /**
     * Loads a resource.
     *
     * @param mixed       $resource The resource
     * @param string|null $type The resource type or null if unknown
     *
     * @throws \Exception If something went wrong
     */
    public function load($resource, $type = null)
    {
        return;
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed       $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return true;
    }

    /**
     * Gets the loader resolver.
     *
     * @return LoaderResolverInterface|null A LoaderResolverInterface instance
     */
    public function getResolver()
    {
        return null;
    }

    /**
     * Sets the loader resolver.
     *
     * @param LoaderResolverInterface $resolver
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        return;
    }
}
