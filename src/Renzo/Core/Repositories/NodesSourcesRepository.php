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
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * EntityRepository that implements search engine query with Solr.
 */
class NodesSourcesRepository extends EntityRepository
{

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
        SecurityContext $securityContext,
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {

        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'ns')
           ->add('from', $this->getEntityName() . ' ns');

        if (!$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $qb->innerJoin('ns.node', 'n', 'WITH', 'n.published = true');
        }

        foreach ($criteria as $key => $value) {

            if (is_array($value)) {
                $res = $qb->expr()->in('ns.' .$key, $value);
            } elseif (is_bool($value)) {
                $res = $qb->expr()->eq('ns.' .$key, (boolean) $value);
            } else {
                $res = $qb->expr()->eq('ns.' .$key, $value);
            }

            $qb->andWhere($res);
        }

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy('ns.'.$key, $value);
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
     * @param SecurityContext $securityContext
     * @param array           $criteria
     * @param array           $orderBy
     * @param integer         $limit
     * @param integer         $offset
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function contextualFindBy(
        SecurityContext $securityContext,
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {

        $qb = $this->getContextualQuery(
            $securityContext,
            $criteria,
            $orderBy,
            $limit,
            $offset
        );

        try {
            return $qb->getQuery()->getResult();
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
     * @param SecurityContext $securityContext
     * @param array           $criteria
     *
     * @return RZ\Renzo\Core\Entities\NodesSources|null
     */
    public function contextualFindOneBy(SecurityContext $securityContext, array $criteria)
    {

        $qb = $this->getContextualQuery(
            $securityContext,
            $criteria,
            null,
            1,
            null
        );

        try {
            return $qb->getQuery()->getSingleResult();
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
