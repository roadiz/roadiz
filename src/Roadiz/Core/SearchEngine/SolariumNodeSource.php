<?php
declare(strict_types=1);
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file SolariumNodeSource.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesIndexingEvent;
use RZ\Roadiz\Markdown\MarkdownInterface;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Wrap a Solarium and a NodeSource together to ease indexing.
 */
class SolariumNodeSource extends AbstractSolarium
{
    const DOCUMENT_TYPE = 'NodesSources';
    const IDENTIFIER_KEY = 'node_source_id_i';

    protected $nodeSource = null;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SolariumNodeSource constructor.
     *
     * @param NodesSources             $nodeSource
     * @param Client                   $client
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface|null     $logger
     * @param MarkdownInterface|null   $markdown
     */
    public function __construct(
        NodesSources $nodeSource,
        Client $client,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger = null,
        MarkdownInterface $markdown = null
    ) {
        parent::__construct($client, $logger, $markdown);
        $this->nodeSource = $nodeSource;
        $this->dispatcher = $dispatcher;
    }

    public function getDocumentId()
    {
        return $this->nodeSource->getId();
    }

    /**
     * Get a key/value array representation of current node-source document.
     *
     * @param bool $subResource Tell when this field gathering is for a main resource indexation or a sub-resource
     *
     * @return array
     * @throws \Exception
     */
    public function getFieldsAssoc(bool $subResource = false): array
    {
        $event = new NodesSourcesIndexingEvent($this->nodeSource, [], $this);

        return $this->dispatcher->dispatch($event)->getAssociations();
    }

    /**
     * Remove any document linked to current node-source.
     *
     * @param Query $update
     * @return boolean
     */
    public function clean(Query $update)
    {
        $update->addDeleteQuery(
            static::IDENTIFIER_KEY . ':"' . $this->nodeSource->getId() . '"' .
            '&'.static::TYPE_DISCRIMINATOR.':"' . static::DOCUMENT_TYPE . '"' .
            '&locale_s:"' . $this->nodeSource->getTranslation()->getLocale() . '"'
        );

        return true;
    }
}
