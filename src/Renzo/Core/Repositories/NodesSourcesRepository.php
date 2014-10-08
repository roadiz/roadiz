<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Repositories;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Security\Core\SecurityContext;
use RZ\Renzo\Core\AbstractEntities\PersistableInterface;

/**
 * EntityRepository that implements search engine query with Solr.
 */
class NodesSourcesRepository extends EntityRepository
{
    /**
     * Add a tag filtering to queryBuilder.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByTag(&$criteria, &$qb)
    {
        if (in_array('tags', array_keys($criteria))) {

            $qb->innerJoin(
                'ns.node',
                'n'
            );

            if (is_array($criteria['tags'])) {
                $qb->innerJoin(
                    'n.tags',
                    'tg',
                    'WITH',
                    'tg.id IN (:tags)'
                );
            } else {
                $qb->innerJoin(
                    'n.tags',
                    'tg',
                    'WITH',
                    'tg.id = :tags'
                );
            }
        }
    }

    /**
     * Bind tag parameters to final query
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyFilterByTag(array &$criteria, &$finalQuery)
    {
        if (in_array('tags', array_keys($criteria))) {
            if (is_object($criteria['tags'])) {
                $finalQuery->setParameter('tags', $criteria['tags']->getId());
            } elseif (is_array($criteria['tags'])) {
                $finalQuery->setParameter('tags', $criteria['tags']);
            } elseif (is_integer($criteria['tags'])) {
                $finalQuery->setParameter('tags', (int) $criteria['tags']);
            }
            unset($criteria['tags']);
        }
    }

    /**
     * Reimplementing findBy features… with extra things.
     *
     * * key => array('<=', $value)
     * * key => array('<', $value)
     * * key => array('>=', $value)
     * * key => array('>', $value)
     * * key => array('BETWEEN', $value, $value)
     * * key => array('LIKE', $value)
     * * key => 'NOT NULL'
     *
     * You even can filter with node fields, examples:
     *
     * * `node.published => true`
     * * `node.nodeName => 'page1'`
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByCriteria(&$criteria, &$qb, &$joinedNode = false)
    {
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {

            if ($key == "tags") {
                continue;
            }

            /*
             * compute prefix for
             * filtering node relation fields
             */
            $prefix = 'ns.';

            // Dots are forbidden in field definitions
            $baseKey = str_replace('.','_',$key);

            if (false !== strpos($key, 'node.')) {
                if (!$joinedNode) {
                    $qb->innerJoin(
                        'ns.node',
                        'n'
                    );
                    $joinedNode = true;
                }

                $prefix = 'n.';
                $key = str_replace('node.', '', $key);
            }


            if (is_object($value) && $value instanceof PersistableInterface) {
                $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
            } elseif (is_array($value)) {
                /*
                 * array
                 *
                 * ['<=', $value]
                 * ['<', $value]
                 * ['>=', $value]
                 * ['>', $value]
                 * ['BETWEEN', $value, $value]
                 * ['LIKE', $value]
                 * in [$value, $value]
                 */
                if (count($value) > 1) {
                    switch ($value[0]) {
                        case '<=':
                            # lte -> $value[1]
                            $res = $qb->expr()->lte($prefix.$key, ':'.$baseKey);
                            break;
                        case '<':
                            # lt -> $value[1]
                            $res = $qb->expr()->lt($prefix.$key, ':'.$baseKey);
                            break;
                        case '>=':
                            # gte -> $value[1]
                            $res = $qb->expr()->gte($prefix.$key, ':'.$baseKey);
                            break;
                        case '>':
                            # gt -> $value[1]
                            $res = $qb->expr()->gt($prefix.$key, ':'.$baseKey);
                            break;
                        case 'BETWEEN':
                            $res = $qb->expr()->between(
                                $prefix.$key,
                                ':'.$baseKey.'_1',
                                ':'.$baseKey.'_2'
                            );
                            break;
                        case 'LIKE':
                            $res = $qb->expr()->like(
                                $prefix.$key,
                                $qb->expr()->literal($value[1])
                            );
                            break;
                        default:
                            $res = $qb->expr()->in($prefix.$key, ':'.$baseKey);
                            break;
                    }
                } else {
                    $res = $qb->expr()->in($prefix.$key, ':'.$baseKey);
                }

            } elseif (is_bool($value)) {
               $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
            }  elseif ($value == 'NOT NULL') {
                $res = $qb->expr()->isNotNull($prefix.$key);
            } elseif (isset($value)) {
                $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
            } elseif (null === $value) {
                $res = $qb->expr()->isNull($prefix.$key);
            }

            $qb->andWhere($res);
        }
    }

    /**
     * Bind parameters to generated query.
     *
     * @param array $criteria
     * @param Query $qb
     */
    protected function applyFilterByCriteria(&$criteria, &$finalQuery)
    {
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {

            if ($key == "tags") {
                continue;
            }

            // Dots are forbidden in field definitions
            $key = str_replace('.','_',$key);

            if (is_object($value) && $value instanceof PersistableInterface) {
                $finalQuery->setParameter($key, $value->getId());
            } elseif (is_array($value)) {

                if (count($value) > 1) {
                    switch ($value[0]) {
                        case '<=':
                        case '<':
                        case '>=':
                        case '>':
                            $finalQuery->setParameter($key, $value[1]);
                            break;
                        case 'BETWEEN':
                            $finalQuery->setParameter($key.'_1', $value[1]);
                            $finalQuery->setParameter($key.'_2', $value[2]);
                            break;
                        case 'LIKE':
                            // no need to bind a parameter here
                            break;
                        default:
                            $finalQuery->setParameter($key, $value);
                            break;
                    }
                } else {
                    $finalQuery->setParameter($key, $value);
                }

            } elseif (is_bool($value)) {
               $finalQuery->setParameter($key, $value);
            }  elseif ($value == 'NOT NULL') {
                // no need to bind a parameter here
            } elseif (isset($value)) {
                $finalQuery->setParameter($key, $value);
            } elseif (null === $value) {
                // no need to bind a parameter here
            }
        }
    }

    /**
     * Create a securized query with node.published = true if user is
     * not a Backend user.
     *
     * @param SecurityContext $securityContext
     * @param array           $criteria
     * @param array\null      $orderBy
     * @param integer|null    $limit
     * @param integer|null    $offset
     *
     * @return QueryBuilder
     */
    protected function getContextualQuery(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        SecurityContext $securityContext = null
    ) {

        $joinedNode = false;
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'ns')
           ->add('from', $this->getEntityName() . ' ns');

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $qb->innerJoin('ns.node', 'n', 'WITH', 'n.published = true');

            $joinedNode = true;
        }

        $this->filterByCriteria($criteria, $qb, $joinedNode);

        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {

                if (false !== strpos($key, 'node.')) {
                    if (!$joinedNode) {
                        $qb->innerJoin('ns.node', 'n');
                    }
                    $simpleKey = str_replace('node.', '', $key);

                    $qb->addOrderBy('n.'.$simpleKey, $value);

                } else {
                    $qb->addOrderBy('ns.'.$key, $value);
                }
            }
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

    /**
     * A secure findBy with which user must be a backend user
     * to see unpublished nodes.
     *
     * @param array           $criteria
     * @param array           $orderBy
     * @param integer         $limit
     * @param integer         $offset
     * @param SecurityContext $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        SecurityContext $securityContext = null
    ) {

        $qb = $this->getContextualQuery(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $securityContext
        );

        $finalQuery = $qb->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);

        //var_dump($finalQuery->getDQL());exit();
        try {
            return $finalQuery->getResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return null;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * A secure findOneBy with which user must be a backend user
     * to see unpublished nodes.
     *
     *
     * @param array           $criteria
     * @param SecurityContext $securityContext
     *
     * @return RZ\Renzo\Core\Entities\NodesSources|null
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        SecurityContext $securityContext = null
    ) {

        $qb = $this->getContextualQuery(
            $criteria,
            $orderBy,
            1,
            null,
            $securityContext
        );

        $finalQuery = $qb->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);


        try {
            return $finalQuery->getSingleResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return null;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

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
            $service = Kernel::getService('solr');

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
            $service = Kernel::getService('solr');

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
