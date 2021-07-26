<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension that allow render documents Url
 */
class UrlExtension extends AbstractExtension
{
    /**
     * @var CacheProvider|null
     */
    protected ?CacheProvider $cacheProvider;
    /**
     * @var DocumentUrlGeneratorInterface
     */
    protected DocumentUrlGeneratorInterface $documentUrlGenerator;
    /**
     * @var bool
     */
    private bool $throwExceptions;

    /**
     * @param DocumentUrlGeneratorInterface $documentUrlGenerator
     * @param CacheProvider|null            $cacheProvider
     * @param bool                          $throwExceptions Trigger exception if using filter on NULL values (default: false)
     */
    public function __construct(
        DocumentUrlGeneratorInterface $documentUrlGenerator,
        CacheProvider $cacheProvider = null,
        bool $throwExceptions = false
    ) {
        $this->cacheProvider = $cacheProvider;
        $this->throwExceptions = $throwExceptions;
        $this->documentUrlGenerator = $documentUrlGenerator;
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
    public function getCacheKey(NodesSources $ns, bool $absolute = false, string $canonicalScheme = '')
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

                $this->documentUrlGenerator->setOptions($criteria);
                $this->documentUrlGenerator->setDocument($mixed);
                return $this->documentUrlGenerator->getUrl($absolute);
            } catch (InvalidArgumentException $e) {
                throw new RuntimeError($e->getMessage(), -1, null, $e);
            }
        }

        throw new RuntimeError("Twig “url” filter can be only used with a Document");
    }
}
