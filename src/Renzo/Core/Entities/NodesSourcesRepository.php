<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * EntityRepository that implements search engine query with Solr
 */
class NodesSourcesRepository extends \RZ\Renzo\Core\Utils\EntityRepository
{
    /**
     * Search nodes sources by using Solr search engine.
     *
     * @param string $query Solr query string (for example: `text:Lorem Ipsum`)
     *
     * @return ArrayCollection | null
     */
    public function findBySearchQuery($query)
    {
        // Update Solr Serach engine if setup
        if (true === Kernel::getInstance()->pingSolrServer()) {
            $service = Kernel::getInstance()->getSolrService();

            $queryObj = $service->createSelect();

            $queryObj->setQuery($query);
            $queryObj->addSort('score', $queryObj::SORT_DESC);

            // this executes the query and returns the result
            $resultset = $service->select($queryObj);

            if (0 === $resultset->getNumFound()) {
                return null;
            } else {
                $sources = new ArrayCollection();

                foreach ($resultset as $document) {
                    $sources->add($this->_em->find(
                        'RZ\Renzo\Core\Entities\NodesSources',
                        $document['node_source_id_i']
                    ));
                }

                return $sources;
            }
        }

        return null;
    }

    /**
     * Search nodes sources by using Solr search engine
     * and a specific translation.
     *
     * @param string      $query       Solr query string (for example: `text:Lorem Ipsum`)
     * @param Translation $translation Current translation
     *
     * @return ArrayCollection | null
     */
    public function findBySearchQueryAndTranslation($query, Translation $translation)
    {
        // Update Solr Serach engine if setup
        if (true === Kernel::getInstance()->pingSolrServer()) {
            $service = Kernel::getInstance()->getSolrService();

            $queryObj = $service->createSelect();

            $queryObj->setQuery($query);
            // create a filterquery
            $queryObj->createFilterQuery('translation')->setQuery('locale_s:'.$translation->getLocale());
            $queryObj->addSort('score', $queryObj::SORT_DESC);

            // this executes the query and returns the result
            $resultset = $service->select($queryObj);

            if (0 === $resultset->getNumFound()) {
                return null;
            } else {
                $sources = new ArrayCollection();

                foreach ($resultset as $document) {
                    $sources->add($this->_em->find(
                        'RZ\Renzo\Core\Entities\NodesSources',
                        $document['node_source_id_i']
                    ));
                }

                return $sources;
            }
        }

        return null;
    }
}
