<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Config\NullLoader;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPathGeneratingEvent;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NodeRouter extends Router implements VersatileGeneratorInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * @var Stopwatch|null
     */
    protected $stopwatch;
    /**
     * @var bool
     */
    protected $preview;
    /**
     * @var ThemeResolverInterface
     */
    private $themeResolver;
    /**
     * @var CacheProvider
     */
    private $nodeSourceUrlCacheProvider;
    /**
     * @var Settings
     */
    private $settingsBag;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * NodeRouter constructor.
     *
     * @param EntityManagerInterface   $em
     * @param ThemeResolverInterface   $themeResolver
     * @param Settings                 $settingsBag
     * @param EventDispatcherInterface $eventDispatcher
     * @param array                    $options
     * @param RequestContext|null      $context
     * @param LoggerInterface|null     $logger
     * @param Stopwatch|null           $stopwatch
     * @param bool                     $preview
     */
    public function __construct(
        EntityManagerInterface $em,
        ThemeResolverInterface $themeResolver,
        Settings $settingsBag,
        EventDispatcherInterface $eventDispatcher,
        array $options = [],
        RequestContext $context = null,
        LoggerInterface $logger = null,
        Stopwatch $stopwatch = null,
        $preview = false
    ) {
        parent::__construct(
            new NullLoader(),
            null,
            $options,
            $context,
            $logger
        );
        $this->em = $em;
        $this->stopwatch = $stopwatch;
        $this->preview = $preview;
        $this->themeResolver = $themeResolver;
        $this->settingsBag = $settingsBag;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    /**
     * @return CacheProvider
     */
    public function getNodeSourceUrlCacheProvider(): CacheProvider
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
        if (null !== $this->matcher) {
            return $this->matcher;
        }
        return $this->matcher = new NodeUrlMatcher(
            $this->context,
            $this->em,
            $this->themeResolver,
            $this->stopwatch,
            $this->logger,
            $this->preview
        );
    }

    /**
     * No generator for a node router.
     */
    public function getGenerator()
    {
        throw new \BadMethodCallException(get_class($this) . ' does not support path generation.');
    }

    /**
     * Whether this generator supports the supplied $name.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     *
     * @param mixed $name The route "name" which may also be an object or anything
     *
     * @return bool
     */
    public function supports($name): bool
    {
        return ($name instanceof NodesSources);
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
            return '['.$name->getTranslation()->getLocale().']' .
                $name->getTitle() . ' - ' .
                $name->getNode()->getNodeName() .
                '['.$name->getNode()->getId().']';
        }
        return (string) $name;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (null === $name || !$name instanceof NodesSources) {
            throw new RouteNotFoundException();
        }

        if (!empty($parameters['canonicalScheme'])) {
            $schemeAuthority = trim($parameters['canonicalScheme']);
            unset($parameters['canonicalScheme']);
        } else {
            $schemeAuthority = $this->getContext()->getScheme() . '://' . $this->getHttpHost();
        }

        $noCache = false;
        if (!empty($parameters['noCache'])) {
            $noCache = (bool)($parameters['noCache']);
            unset($parameters['noCache']);
        }

        $nodePathInfo = $this->getResourcePath($name, $parameters, $noCache);

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
