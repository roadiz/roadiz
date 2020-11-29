<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Symfony\Component\Routing\RouteCollection;

/**
 * Extends Symfony2 RouteCollection to add parseResources()
 * method to defer route loading.
 *
 * TODO: Use \Symfony\Cmf\Component\Routing\LazyRouteCollection
 */
class DeferredRouteCollection extends RouteCollection
{
    /**
     * Method to parse and get routes from external resources
     * in deferred way to your collection.
     *
     * Useful if you want to use a caching system on your Router
     * and parse Yaml file only when cache is not available.
     */
    public function parseResources()
    {
    }
}
