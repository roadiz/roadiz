<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

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
            new TwigFilter('url', [$this, 'getUrl']),
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
     * @throws RuntimeError
     */
    public function getUrl(AbstractEntity $mixed = null, array $criteria = [])
    {
        if (null === $mixed) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Twig “url” filter must be used with a not null object");
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
                throw new RuntimeError($e->getMessage(), -1, null, $e);
            }
        }

        throw new RuntimeError("Twig “url” filter can be only used with a Document");
    }
}
