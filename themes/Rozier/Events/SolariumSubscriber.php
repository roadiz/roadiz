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
 * @file SolariumSubscriber.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Events;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Events\FilterNodeEvent;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodeEvents;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use Solarium\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Node and NodesSources event to update
 * a Solr server documents.
 */
class SolariumSubscriber implements EventSubscriberInterface
{
    protected $solr;
    protected $logger;

    public function __construct(Client $solr, LoggerInterface $logger)
    {
        $this->solr = $solr;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return array(
            NodesSourcesEvents::NODE_SOURCE_UPDATED => 'onSolariumSingleUpdate',
            NodesSourcesEvents::NODE_SOURCE_DELETED => 'onSolariumSingleDelete',
            NodeEvents::NODE_DELETED => 'onSolariumNodeDelete',
            NodeEvents::NODE_UNDELETED => 'onSolariumNodeUpdate',
            NodeEvents::NODE_TAGGED => 'onSolariumNodeUpdate',
        );
    }

    /**
     * Update or create solr document for current Node-source.
     *
     * @param  FilterNodesSourcesEvent $event
     */
    public function onSolariumSingleUpdate(FilterNodesSourcesEvent $event)
    {
        // Update Solr Serach engine if setup
        if (null !== $this->solr) {
            $solrSource = new SolariumNodeSource(
                $event->getNodeSource(),
                $this->solr
            );
            $solrSource->getDocumentFromIndex();
            $solrSource->updateAndCommit();
        }
    }

    /**
     * Delete solr document for current Node-source.
     *
     * @param  FilterNodesSourcesEvent $event
     */
    public function onSolariumSingleDelete(FilterNodesSourcesEvent $event)
    {
        // Update Solr Serach engine if setup
        if (null !== $this->solr) {
            $solrSource = new SolariumNodeSource(
                $event->getNodeSource(),
                $this->solr
            );
            $solrSource->getDocumentFromIndex();
            $solrSource->removeAndCommit();
        }
    }

    /**
     * Delete solr documents for each Node sources.
     *
     * @param  FilterNodesSourcesEvent $event
     */
    public function onSolariumNodeDelete(FilterNodeEvent $event)
    {
        if (null !== $this->solr) {
            foreach ($event->getNode()->getNodeSources() as $nodeSource) {
                $solrSource = new SolariumNodeSource(
                    $nodeSource,
                    $this->solr
                );
                $solrSource->getDocumentFromIndex();
                $solrSource->removeAndCommit();
            }
        }
    }

    /**
     * Update or create solr documents for each Node sources.
     *
     * @param  FilterNodesSourcesEvent $event
     */
    public function onSolariumNodeUpdate(FilterNodeEvent $event)
    {
        if (null !== $this->solr) {
            foreach ($event->getNode()->getNodeSources() as $nodeSource) {
                $solrSource = new SolariumNodeSource(
                    $nodeSource,
                    $this->solr
                );
                $solrSource->getDocumentFromIndex();
                $solrSource->updateAndCommit();
            }
        }
    }
}
