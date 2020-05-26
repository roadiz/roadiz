<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\SearchEngine\AbstractSolarium;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FilterSolariumNodeSourceEvent
 *
 * @package RZ\Roadiz\Core\Events
 * @deprecated
 */
class FilterSolariumNodeSourceEvent extends Event
{
    /**
     * @var NodesSources
     */
    protected $nodeSource;
    /**
     * @var array
     */
    protected $associations;
    /**
     * @var AbstractSolarium|null
     */
    protected $solariumDocument;
    /**
     * @var bool
     */
    protected $subResource;

    /**
     * FilterSolariumNodeSourceEvent constructor.
     *
     * @param NodesSources     $nodeSource
     * @param array            $associations
     * @param AbstractSolarium $solariumDocument
     * @param bool             $subResource
     */
    public function __construct(
        NodesSources $nodeSource,
        array $associations,
        AbstractSolarium $solariumDocument,
        bool $subResource = false
    ) {
        $this->nodeSource = $nodeSource;
        $this->associations = $associations;
        $this->solariumDocument = $solariumDocument;
        $this->subResource = $subResource;
    }

    public function getNodeSource(): NodesSources
    {
        return $this->nodeSource;
    }

    /**
     * Get Solr document data to index.
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->associations;
    }

    /**
     * Set Solr document data to index.
     *
     * @param array $associations
     * @return FilterSolariumNodeSourceEvent
     */
    public function setAssociations($associations)
    {
        $this->associations = $associations;
        return $this;
    }

    /**
     * @return AbstractSolarium|null
     */
    public function getSolariumDocument(): ?AbstractSolarium
    {
        return $this->solariumDocument;
    }

    /**
     * @param AbstractSolarium $solariumDocument
     *
     * @return FilterSolariumNodeSourceEvent
     */
    public function setSolariumDocument(AbstractSolarium $solariumDocument): FilterSolariumNodeSourceEvent
    {
        $this->solariumDocument = $solariumDocument;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSubResource(): bool
    {
        return $this->subResource;
    }
}
