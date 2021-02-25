<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\Common\Cache\CacheProvider;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Config\NullLoader;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPathGeneratingEvent;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

class NodeRouter extends Router implements VersatileGeneratorInterface
{
    /**
     * @var string
     */
    const NO_CACHE_PARAMETER = '_no_cache';
    private ThemeResolverInterface $themeResolver;
    private ?CacheProvider $nodeSourceUrlCacheProvider = null;
    private ParameterBag $settingsBag;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @param UrlMatcherInterface $matcher
     * @param ThemeResolverInterface $themeResolver
     * @param ParameterBag $settingsBag
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $options
     * @param RequestContext|null $context
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        UrlMatcherInterface $matcher,
        ThemeResolverInterface $themeResolver,
        ParameterBag $settingsBag,
        EventDispatcherInterface $eventDispatcher,
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
        $this->themeResolver = $themeResolver;
        $this->settingsBag = $settingsBag;
        $this->eventDispatcher = $eventDispatcher;
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    /**
     * @return CacheProvider|null
     */
    public function getNodeSourceUrlCacheProvider(): ?CacheProvider
    {
        return $this->nodeSourceUrlCacheProvider;
    }

    /**
     * @param CacheProvider $nodeSourceUrlCacheProvider
     */
    public function setNodeSourceUrlCacheProvider(CacheProvider $nodeSourceUrlCacheProvider): void
    {
        $this->nodeSourceUrlCacheProvider = $nodeSourceUrlCacheProvider;
    }

    /**
     * Gets the UrlMatcher instance associated with this Router.
     *
     * @return UrlMatcherInterface
     */
    public function getMatcher(): UrlMatcherInterface
    {
        return $this->matcher;
    }

    /**
     * No generator for a node router.
     */
    public function getGenerator()
    {
        throw new \BadMethodCallException(get_class($this) . ' does not support path generation.');
    }

    /**
     * @inheritDoc
     */
    public function supports($name): bool
    {
        return ($name instanceof NodesSources || $name === RouteObjectInterface::OBJECT_BASED_ROUTE_NAME);
    }

    /**
     * Convert a route identifier (name, content object etc) into a string
     * usable for logging and other debug/error messages
     *
     * @param mixed $name
     * @param array $parameters which should contain a content field containing
     *                          a RouteReferrersReadInterface object
     *
     * @return string
     */
    public function getRouteDebugMessage($name, array $parameters = []): string
    {
        if ($name instanceof NodesSources) {
            @trigger_error('Passing an object as route name is deprecated since version 1.5. Pass the `RouteObjectInterface::OBJECT_BASED_ROUTE_NAME` as route name and the object in the parameters with key `RouteObjectInterface::ROUTE_OBJECT` resp the content id with content_id.', E_USER_DEPRECATED);
            return '['.$name->getTranslation()->getLocale().']' .
                $name->getTitle() . ' - ' .
                $name->getNode()->getNodeName() .
                '['.$name->getNode()->getId().']';
        } elseif (RouteObjectInterface::OBJECT_BASED_ROUTE_NAME === $name) {
            if (array_key_exists(RouteObjectInterface::ROUTE_OBJECT, $parameters) &&
                $parameters[RouteObjectInterface::ROUTE_OBJECT] instanceof NodesSources) {
                $route = $parameters[RouteObjectInterface::ROUTE_OBJECT];
                return '['.$route->getTranslation()->getLocale().']' .
                    $route->getTitle() . ' - ' .
                    $route->getNode()->getNodeName() .
                    '['.$route->getNode()->getId().']';
            }
        }
        return (string) $name;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (!is_string($name)) {
            @trigger_error('Passing an object as route name is deprecated since version 1.5. Pass the `RouteObjectInterface::OBJECT_BASED_ROUTE_NAME` as route name and the object in the parameters with key `RouteObjectInterface::ROUTE_OBJECT` resp the content id with content_id.', E_USER_DEPRECATED);
            $route = $name;
        } elseif (RouteObjectInterface::OBJECT_BASED_ROUTE_NAME === $name) {
            if (array_key_exists(RouteObjectInterface::ROUTE_OBJECT, $parameters) &&
                $parameters[RouteObjectInterface::ROUTE_OBJECT] instanceof NodesSources) {
                $route = $parameters[RouteObjectInterface::ROUTE_OBJECT];
                unset($parameters[RouteObjectInterface::ROUTE_OBJECT]);
            } else {
                $route = null;
            }
        } else {
            $route = null;
        }

        if (null === $route || !$route instanceof NodesSources) {
            throw new RouteNotFoundException();
        }

        if (!empty($parameters['canonicalScheme'])) {
            $schemeAuthority = trim($parameters['canonicalScheme']);
            unset($parameters['canonicalScheme']);
        } else {
            $schemeAuthority = $this->getContext()->getScheme() . '://' . $this->getHttpHost();
        }

        $noCache = false;
        if (!empty($parameters[static::NO_CACHE_PARAMETER])) {
            $noCache = (bool)($parameters[static::NO_CACHE_PARAMETER]);
        }

        $nodePathInfo = $this->getResourcePath($route, $parameters, $noCache);

        /*
         * If node path is complete, do not alter path any more.
         */
        if (true === $nodePathInfo->isComplete()) {
            if ($referenceType == self::ABSOLUTE_URL && !$nodePathInfo->containsScheme()) {
                return $schemeAuthority . $nodePathInfo->getPath();
            }
            return $nodePathInfo->getPath();
        }

        $queryString = '';
        $parameters = $nodePathInfo->getParameters();
        $matcher = $this->getMatcher();

        if (isset($parameters['_format']) &&
            $matcher instanceof NodeUrlMatcher &&
            in_array($parameters['_format'], $matcher->getSupportedFormatExtensions())) {
            unset($parameters['_format']);
        }
        if (array_key_exists(static::NO_CACHE_PARAMETER, $parameters)) {
            unset($parameters[static::NO_CACHE_PARAMETER]);
        }
        if (count($parameters) > 0) {
            $queryString = '?' . http_build_query($parameters);
        }

        if ($referenceType == self::ABSOLUTE_URL) {
            // Absolute path
            return $schemeAuthority . $this->getContext()->getBaseUrl() . '/' . $nodePathInfo->getPath() . $queryString;
        }

        // ABSOLUTE_PATH
        return $this->getContext()->getBaseUrl() . '/' . $nodePathInfo->getPath() . $queryString;
    }

    /**
     * @param NodesSources $source
     * @param array        $parameters
     * @param bool         $noCache
     *
     * @return NodePathInfo
     */
    protected function getResourcePath(NodesSources $source, $parameters = [], bool $noCache = false): NodePathInfo
    {
        if ($noCache) {
            $cacheKey = $source->getId() . '_' .  $this->getContext()->getHost() . '_' . serialize($parameters);
            if (null !== $this->nodeSourceUrlCacheProvider) {
                if (!$this->nodeSourceUrlCacheProvider->contains($cacheKey)) {
                    $this->nodeSourceUrlCacheProvider->save(
                        $cacheKey,
                        $this->getNodesSourcesPath($source, $parameters)
                    );
                }
                return $this->nodeSourceUrlCacheProvider->fetch($cacheKey);
            }
        }

        return $this->getNodesSourcesPath($source, $parameters);
    }

    /**
     * @param NodesSources $source
     * @param array        $parameters
     *
     * @return NodePathInfo
     */
    protected function getNodesSourcesPath(NodesSources $source, $parameters = []): NodePathInfo
    {
        $theme = $this->themeResolver->findTheme($this->getContext()->getHost());
        $event = new NodesSourcesPathGeneratingEvent(
            $theme,
            $source,
            $this->getContext(),
            $parameters,
            (boolean) $this->settingsBag->get('force_locale'),
            (boolean) $this->settingsBag->get('force_locale_with_urlaliases')
        );
        /*
         * Dispatch node-source URL generation to any listener
         */
        $this->eventDispatcher->dispatch($event);
        /*
         * Get path, parameters and isComplete back from event propagation.
         */
        $nodePathInfo = new NodePathInfo();
        $nodePathInfo->setPath($event->getPath());
        $nodePathInfo->setParameters($event->getParameters());
        $nodePathInfo->setComplete($event->isComplete());
        $nodePathInfo->setContainsScheme($event->containsScheme());

        if (null === $nodePathInfo->getPath()) {
            throw new InvalidParameterException('NodeSource generated path is null.');
        }
        return $nodePathInfo;
    }

    /**
     * Returns the HTTP host being requested.
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * @return string
     */
    private function getHttpHost(): string
    {
        $scheme = $this->getContext()->getScheme();

        $port = '';
        if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
            $port = ':'.$this->context->getHttpPort();
        } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
            $port = ':'.$this->context->getHttpsPort();
        }

        return $this->getContext()->getHost() . $port;
    }
}
