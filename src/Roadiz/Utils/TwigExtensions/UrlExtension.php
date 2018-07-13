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
 * @file UrlExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;

/**
 * Extension that allow render documents Url
 */
class UrlExtension extends AbstractExtension
{
    protected $forceLocale;
    protected $cacheProvider;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var bool
     */
    private $throwExceptions;
    /**
     * @var Packages
     */
    private $packages;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * UrlExtension constructor.
     * @param RequestStack $requestStack
     * @param Packages $packages
     * @param UrlGeneratorInterface $urlGenerator
     * @param CacheProvider|null $cacheProvider
     * @param bool $forceLocale
     * @param bool $throwExceptions Trigger exception if using filter on NULL values (default: false)
     */
    public function __construct(
        RequestStack $requestStack,
        Packages $packages,
        UrlGeneratorInterface $urlGenerator,
        CacheProvider $cacheProvider = null,
        $forceLocale = false,
        $throwExceptions = false
    ) {
        $this->forceLocale = $forceLocale;
        $this->cacheProvider = $cacheProvider;
        $this->requestStack = $requestStack;
        $this->throwExceptions = $throwExceptions;
        $this->packages = $packages;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('url', [$this, 'getUrl']),
        ];
    }

    /**
     * @param NodesSources $ns
     * @param bool $absolute
     * @param string $canonicalScheme
     * @deprecated Use ChainRouter::generate method instead. In Twig you can use {{ path(nodeSource) }} or {{ url(nodeSource) }}
     * @return string
     */
    public function getCacheKey(NodesSources $ns, $absolute = false, $canonicalScheme = '')
    {
        return ($ns->getId() . "_" . (int) $absolute . "_" . $canonicalScheme);
    }

    /**
     * Convert an AbstractEntity to an Url.
     *
     * Compatible AbstractEntity:
     *
     * - Document
     *
     * @param  AbstractEntity|null $mixed
     * @param  array $criteria
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function getUrl(AbstractEntity $mixed = null, array $criteria = [])
    {
        if (null === $mixed) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Twig “url” filter must be used with a not null object");
            } else {
                return "";
            }
        }

        if ($mixed instanceof Document) {
            try {
                $absolute = false;
                if (isset($criteria['absolute'])) {
                    $absolute = (boolean) $criteria['absolute'];
                }

                $urlGenerator = new DocumentUrlGenerator(
                    $this->requestStack,
                    $this->packages,
                    $this->urlGenerator,
                    $mixed,
                    $criteria
                );
                return $urlGenerator->getUrl($absolute);
            } catch (InvalidArgumentException $e) {
                throw new \Twig_Error_Runtime($e->getMessage(), -1, null, $e);
            }
        } elseif ($mixed instanceof NodesSources) {
            return $this->getNodesSourceUrl($mixed, $criteria);
        } elseif ($mixed instanceof Node) {
            return $this->getNodeUrl($mixed, $criteria);
        }
        throw new \Twig_Error_Runtime("Twig “url” filter can be only used with a Document, a NodesSources or a Node");
    }

    /**
     * Get nodeSource url using cache.
     *
     * @param NodesSources $ns
     * @param array $criteria
     * @deprecated Use ChainRouter::generate method instead. In Twig you can use {{ path(nodeSource) }} or {{ url(nodeSource) }}
     * @return string
     */
    public function getNodesSourceUrl(NodesSources $ns, array $criteria = [])
    {
        trigger_error('url filter is deprecated for NodesSources. In Twig you can use {{ path(nodeSource) }} or {{ url(nodeSource) }}', E_USER_DEPRECATED);
        $absolute = false;
        $canonicalScheme = '';

        if (isset($criteria['absolute'])) {
            $absolute = (boolean) $criteria['absolute'];
        }
        if (isset($criteria['canonicalScheme'])) {
            $canonicalScheme = trim($criteria['canonicalScheme']);
        }

        $cacheKey = $this->getCacheKey($ns, $absolute, $canonicalScheme);

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        } else {
            $urlGenerator = new NodesSourcesUrlGenerator(
                $this->requestStack->getCurrentRequest(),
                $ns,
                $this->forceLocale
            );

            $url = $urlGenerator->getUrl($absolute, $canonicalScheme);

            $this->cacheProvider->save($cacheKey, $url);
            return $url;
        }
    }

    /**
     * Get node url using its first source.
     *
     * @param Node $node
     * @param array $criteria
     * @deprecated Use ChainRouter::generate method instead. In Twig you can use {{ path(nodeSource) }} or {{ url(nodeSource) }}
     * @return string
     */
    public function getNodeUrl(Node $node, array $criteria = [])
    {
        trigger_error('url filter is deprecated for Node. In Twig you can use {{ path(nodeSource) }} or {{ url(nodeSource) }}', E_USER_DEPRECATED);
        return $this->getNodesSourceUrl($node->getNodeSources()->first(), $criteria);
    }
}
