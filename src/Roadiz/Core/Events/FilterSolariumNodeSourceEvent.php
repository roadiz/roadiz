<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file FilterSolariumNodeSourceEvent.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

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
