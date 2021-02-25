<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Config\NullLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * Router class which takes a DeferredRouteCollection instead of YamlLoader.
 */
class StaticRouter extends Router
{
    protected DeferredRouteCollection $routeCollection;

    /**
     * @param DeferredRouteCollection $routeCollection
     * @param array $options
     * @param RequestContext|null $context
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        DeferredRouteCollection $routeCollection,
        array $options = [],
        RequestContext $context = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct(
            new NullLoader(),
            null,
            $options,
            $context,
            $logger
        );
        $this->routeCollection = $routeCollection;
    }

    /**
     * @return null|DeferredRouteCollection|RouteCollection
     */
    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->routeCollection->parseResources();
            $this->collection = $this->routeCollection;
        }
        return $this->collection;
    }
}
