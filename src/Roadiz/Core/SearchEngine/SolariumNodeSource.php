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

use Doctrine\Common\Collections\Criteria;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesIndexingEvent;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
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
     * @var HandlerFactory
     */
    private $handlerFactory;
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
     * @param HandlerFactory           $handlerFactory
     * @param LoggerInterface|null     $logger
     * @param MarkdownInterface|null   $markdown
     */
    public function __construct(
        NodesSources $nodeSource,
        Client $client,
        EventDispatcherInterface $dispatcher,
        HandlerFactory $handlerFactory,
        LoggerInterface $logger = null,
        MarkdownInterface $markdown = null
    ) {
        parent::__construct($client, $logger, $markdown);
        $this->nodeSource = $nodeSource;
        $this->dispatcher = $dispatcher;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * Get document from Solr index.
     *
     * @return boolean *FALSE* if no document found linked to current node-source.
     */
    public function getDocumentFromIndex()
    {
        $query = $this->client->createSelect();
        $query->setQuery(static::IDENTIFIER_KEY . ':' . $this->nodeSource->getId());
        $query->createFilterQuery('type')->setQuery(static::TYPE_DISCRIMINATOR . ':' . static::DOCUMENT_TYPE);

        // this executes the query and returns the result
        $resultset = $this->client->select($query);

        if (0 === $resultset->getNumFound()) {
            return false;
        } else {
            foreach ($resultset as $document) {
                $this->document = $document;
                return true;
            }
        }
        return false;
    }

    /**
     * Get a key/value array representation of current node-source document.
     * @return array
     * @throws \Exception
     */
    public function getFieldsAssoc(): array
    {
        $assoc = [];
        $collection = [];
        $node = $this->nodeSource->getNode();

        if (null === $node) {
            throw new \RuntimeException("No node relation found for source: " . $this->nodeSource->getTitle(), 1);
        }

        // Need a documentType field
        $assoc[static::TYPE_DISCRIMINATOR] = static::DOCUMENT_TYPE;
        // Need a nodeSourceId field
        $assoc[static::IDENTIFIER_KEY] = $this->nodeSource->getId();
        $assoc['node_type_s'] = $node->getNodeType()->getName();
        $assoc['node_name_s'] = $node->getNodeName();
        $assoc['node_status_i'] = $node->getStatus();
        $assoc['node_visible_b'] = $node->isVisible();

        // Need a locale field
        $locale = $this->nodeSource->getTranslation()->getLocale();
        $lang = \Locale::getPrimaryLanguage($locale);
        $assoc['locale_s'] = $locale;
        /** @var NodesSourcesHandler $handler */
        $handler = $this->handlerFactory->getHandler($this->nodeSource);
        $out = array_map(
            function (Tag $x) {
                return $x->getTranslatedTags()->first() ?
                    $x->getTranslatedTags()->first()->getName() :
                    $x->getTagName();
            },
            $handler->getTags()
        );
        // Use tags_txt to be compatible with other data types
        $assoc['tags_txt'] = $out;

        $assoc['title'] = $this->nodeSource->getTitle();
        $assoc['title_txt_' . $lang] = $this->nodeSource->getTitle();

        $assoc['created_at_dt'] = $node->getCreatedAt()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
        $assoc['updated_at_dt'] = $node->getUpdatedAt()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');

        if (null !== $this->nodeSource->getPublishedAt()) {
            $assoc['published_at_dt'] = $this->nodeSource->getPublishedAt()
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z');
        }
        $assoc['title_txt_' . $lang] = $this->nodeSource->getTitle();
        $collection[] = $this->nodeSource->getTitle();

        $criteria = new Criteria();
        $criteria->andWhere(Criteria::expr()->eq("type", AbstractField::BOOLEAN_T));
        $booleanFields = $node->getNodeType()->getFields()->matching($criteria);

        /** @var NodeTypeField $booleanField */
        foreach ($booleanFields as $booleanField) {
            $name = $booleanField->getName();
            $name .= '_b';
            $getter = $booleanField->getGetterName();
            $assoc[$name] = $this->nodeSource->$getter();
        }

        $searchableFields = $node->getNodeType()->getSearchableFields();
        /** @var NodeTypeField $field */
        foreach ($searchableFields as $field) {
            $name = $field->getName();
            $getter = $field->getGetterName();
            $content = $this->nodeSource->$getter();
            /*
             * Strip markdown syntax
             */
            $content = $this->cleanTextContent($content);

            /*
             * Use locale to create field name
             * with right language
             */
            if (in_array($lang, static::$availableLocalizedTextFields)) {
                $name .= '_txt_' . $lang;
            } else {
                $name .= '_t';
            }

            $assoc[$name] = $content;
            $collection[] = $content;
        }

        /*
         * Collect data in a single field
         * for global search
         */
        $assoc['collection_txt'] = $collection;

        $event = new NodesSourcesIndexingEvent($this->nodeSource, $assoc, $this);
        /*
         * Override associations
         */
        $assoc = $this->dispatcher->dispatch($event)->getAssociations();

        return $assoc;
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
