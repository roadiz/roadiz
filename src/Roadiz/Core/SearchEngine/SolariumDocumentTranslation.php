<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
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
 * @file SolariumDocumentTranslation.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Parsedown;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\FolderTranslation;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Query;

/**
 * Wrap a Solarium and a DocumenTranslation together to ease indexing.
 *
 * @package RZ\Roadiz\Core\SearchEngine
 */
class SolariumDocumentTranslation extends AbstractSolarium
{
    const DOCUMENT_TYPE = 'DocumentTranslation';
    const IDENTIFIER_KEY = 'document_translation_id_i';

    /** @var Document */
    protected $rzDocument = null;

    /** @var DocumentTranslation */
    protected $documentTranslation = null;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Create a new SolariumDocument.
     *
     * @param DocumentTranslation $documentTranslation
     * @param EntityManager $entityManager
     * @param Client $client
     * @param Logger $logger
     */
    public function __construct(
        DocumentTranslation $documentTranslation,
        EntityManager $entityManager,
        Client $client = null,
        Logger $logger = null
    ) {
        if (null === $client) {
            throw new SolrServerNotConfiguredException("No Solr server available", 1);
        }

        $this->client = $client;
        $this->documentTranslation = $documentTranslation;
        $this->rzDocument = $documentTranslation->getDocument();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * Get document fron Solr index.
     *
     * @return boolean *FALSE* if no document found linked to current roadiz document.
     */
    public function getDocumentFromIndex()
    {
        $query = $this->client->createSelect();
        $query->setQuery(static::IDENTIFIER_KEY . ':' . $this->documentTranslation->getId());
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
    protected function getFieldsAssoc()
    {
        $assoc = [];
        $collection = [];

        // Need a documentType field
        $assoc[static::TYPE_DISCRIMINATOR] = static::DOCUMENT_TYPE;
        // Need a nodeSourceId field
        $assoc[static::IDENTIFIER_KEY] = $this->documentTranslation->getId();
        $assoc['document_id_i'] = $this->rzDocument->getId();

        $assoc['filename_s'] = $this->rzDocument->getFilename();
        $assoc['mime_type_s'] = $this->rzDocument->getMimeType();

        $translation = $this->documentTranslation->getTranslation();
        $locale = $translation->getLocale();
        $assoc['locale_s'] = $locale;
        $lang = \Locale::getPrimaryLanguage($locale);

        /*
         * Use locale to create field name
         * with right language
         */
        $suffix = '_t';
        if (in_array($lang, static::$availableLocalizedTextFields)) {
            $suffix = '_txt_' . $lang;
        }

        $assoc['title'] = $this->documentTranslation->getName();

        /*
         * Remove ctrl characters
         */
        $description = strip_tags(Parsedown::instance()->text($this->documentTranslation->getDescription()));
        $description = preg_replace("[:cntrl:]", "", $description);
        $description = preg_replace('/[\x00-\x1F]/', '', $description);
        $assoc['description' . $suffix] = $description;

        $assoc['copyright' . $suffix] = $this->documentTranslation->getCopyright();

        $collection[] = $assoc['title'];
        $collection[] = $assoc['description' . $suffix];
        $collection[] = $assoc['copyright' . $suffix];


        $folders = $this->rzDocument->getFolders();
        $folderNames = [];
        /** @var Folder $folder */
        foreach ($folders as $folder) {
            if ($fTrans = $folder->getTranslatedFoldersByTranslation($translation)->first()) {
                $folderNames[] = $fTrans->getName();
            }
        }

        if ($this->logger !== null && count($folderNames) > 0) {
            $this->logger->debug('Indexed document.', [
                'document' => $this->rzDocument->getId(),
                'locale' => $this->documentTranslation->getTranslation()->getLocale(),
                'folders' => $folderNames,
            ]);
        }

        // Use tags_txt to be compatible with other data types
        $assoc['tags_txt'] = $folderNames;

        /*
         * Collect data in a single field
         * for global search
         */
        $assoc['collection_txt'] = $collection;

        return $assoc;
    }

    /**
     * Remove any document linked to current node-source.
     *
     * @param \Solarium\QueryType\Update\Query\Query $update
     * @return boolean
     */
    public function clean(Query $update)
    {
        $update->addDeleteQuery(
            static::IDENTIFIER_KEY . ':"' . $this->documentTranslation->getId() . '"' .
            '&' . static::TYPE_DISCRIMINATOR . ':"' . static::DOCUMENT_TYPE . '"' .
            '&locale_s:"' . $this->documentTranslation->getTranslation()->getLocale() . '"'
        );

        return true;
    }
}
