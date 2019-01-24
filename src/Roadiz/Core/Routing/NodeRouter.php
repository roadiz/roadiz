<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeRouter.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Stopwatch\Stopwatch;

class NodeRouter extends Router implements VersatileGeneratorInterface
{
    protected $em;
    protected $stopwatch;

    /**
     * @var bool
     */
    protected $preview;

    /**
     * @var ThemeResolverInterface
     */
    private $themeResolver;

    /** @var CacheProvider */
    private $nodeSourceUrlCacheProvider;

    /**
     * @var Settings
     */
    private $settingsBag;

    /**
     * NodeRouter constructor.
     *
     * @param EntityManager $em
     * @param ThemeResolverInterface $themeResolver
     * @param Settings $settingsBag
     * @param array $options
     * @param RequestContext|null $context
     * @param LoggerInterface|null $logger
     * @param Stopwatch|null $stopwatch
     * @param bool $preview
     */
    public function __construct(
        EntityManager $em,
        ThemeResolverInterface $themeResolver,
        Settings $settingsBag,
        array $options = [],
        RequestContext $context = null,
        LoggerInterface $logger = null,
        Stopwatch $stopwatch = null,
        $preview = false
    ) {
        $this->em = $em;
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
        $this->context = $context ?: new RequestContext();
        $this->setOptions($options);
        $this->preview = $preview;
        $this->themeResolver = $themeResolver;
        $this->settingsBag = $settingsBag;
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
     * @return NodeUrlMatcher
     */
    public function getMatcher(): NodeUrlMatcher
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
     *
     * @return null
     */
    public function getGenerator()
    {
        return null;
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

        $resourcePath = $this->getResourcePath($name, $parameters);

        if (!empty($parameters['canonicalScheme'])) {
            $schemeAuthority = trim($parameters['canonicalScheme']);
            unset($parameters['canonicalScheme']);
        } else {
            $schemeAuthority = $this->getContext()->getScheme() . '://' . $this->getHttpHost();
        }

        $queryString = '';
        if (isset($parameters['_format']) &&
            in_array($parameters['_format'], $this->getMatcher()->getSupportedFormatExtensions())) {
            unset($parameters['_format']);
        }
        if (count($parameters) > 0) {
            $queryString = '?' . http_build_query($parameters);
        }

        if ($referenceType == self::ABSOLUTE_URL) {
            // Absolute path
            return $schemeAuthority . $this->getContext()->getBaseUrl() . '/' . $resourcePath . $queryString;
        }

        // ABSOLUTE_PATH
        return $this->getContext()->getBaseUrl() . '/' . $resourcePath . $queryString;
    }

    /**
     * @param NodesSources $source
     * @param array        $parameters
     * @return string
     */
    protected function getResourcePath(NodesSources $source, $parameters = []): string
    {
        $cacheKey = $source->getId() . '_' .  $this->getContext()->getHost() . '_' . serialize($parameters);
        if (null !== $this->nodeSourceUrlCacheProvider) {
            if (!$this->nodeSourceUrlCacheProvider->contains($cacheKey)) {
                $theme = $this->themeResolver->findTheme($this->getContext()->getHost());
                $urlGenerator = new NodesSourcesUrlGenerator(
                    null,
                    $source,
                    (boolean) $this->settingsBag->get('force_locale')
                );
                $this->nodeSourceUrlCacheProvider->save(
                    $cacheKey,
                    $urlGenerator->getNonContextualUrl($theme, $parameters)
                );
            }
            return $this->nodeSourceUrlCacheProvider->fetch($cacheKey);
        } else {
            $theme = $this->themeResolver->findTheme($this->getContext()->getHost());
            $urlGenerator = new NodesSourcesUrlGenerator(
                null,
                $source,
                (boolean) $this->settingsBag->get('force_locale')
            );
            return $urlGenerator->getNonContextualUrl($theme, $parameters);
        }
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
