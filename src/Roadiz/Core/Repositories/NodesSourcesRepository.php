<?php
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
 * @file NodesSourcesRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\QueryException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * EntityRepository that implements search engine query with Solr.
 */
class NodesSourcesRepository extends EntityRepository
{
    /**
     * Add a tag filtering to queryBuilder.
     *
     * @param array $criteria
     * @param QueryBuilder $qb
     * @param $joinedNode
     */
    protected function filterByTag(&$criteria, &$qb, &$joinedNode)
    {
        if (in_array('tags', array_keys($criteria))) {
            if (!$joinedNode) {
                $qb->innerJoin(
                    'ns.node',
                    'n'
                );
                $joinedNode = true;
            }

            $this->buildTagFiltering($criteria, $qb);
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
            if ($criteria['tags'] instanceof Tag) {
                $finalQuery->setParameter('tags', $criteria['tags']->getId());
            } elseif (is_array($criteria['tags']) ||
                $criteria['tags'] instanceof Collection) {
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
     * * key => array('NOT IN', $array)
     * * key => 'NOT NULL'
     *
     * You even can filter with node fields, examples:
     *
     * * `node.published => true`
     * * `node.nodeName => 'page1'`
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     * @param boolean $joinedNode
     * @param boolean $joinedNodeType
     */
    protected function filterByCriteria(
        &$criteria,
        &$qb,
        &$joinedNode = false,
        &$joinedNodeType = false
    ) {
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {
            if ($key == "tags" || $key == "tagExclusive") {
                continue;
            }

            /*
             * compute prefix for
             * filtering node relation fields
             */
            $prefix = 'ns.';

            // Dots are forbidden in field definitions
            $baseKey = str_replace('.', '_', $key);

            if (false !== strpos($key, 'node.nodeType.')) {
                if (!$joinedNode) {
                    $qb->innerJoin(
                        'ns.node',
                        'n'
                    );
                    $joinedNode = true;
                }
                if (!$joinedNodeType) {
                    $qb->innerJoin(
                        'n.nodeType',
                        'nt'
                    );
                    $joinedNodeType = true;
                }

                $prefix = 'nt.';
                $key = str_replace('node.nodeType.', '', $key);
            }

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

            $qb->andWhere($this->buildComparison($value, $prefix, $key, $baseKey, $qb));
        }
    }

    /**
     * Direct bind one single parameter without preparation.
     *
     * @param string       $key
     * @param mixed        $value
     * @param QueryBuilder $qb
     * @param string       $alias
     *
     * @return QueryBuilder
     */
    protected function singleDirectComparison($key, &$value, QueryBuilder $qb, $alias)
    {
        if (false !== strpos($key, 'node.')) {
            if (!$this->hasJoinedNode($qb, $alias)) {
                $qb->innerJoin($alias . '.node', 'n');
            }

            $prefix = 'n';
            $prefixedkey = str_replace('node.', '', $key);
            return parent::singleDirectComparison($prefixedkey, $value, $qb, $prefix);
        } else {
            return parent::singleDirectComparison($key, $value, $qb, $alias);
        }
    }

    /**
     * Bind parameters to generated query.
     *
     * @param array $criteria
     * @param $finalQuery
     */
    protected function applyFilterByCriteria(&$criteria, &$finalQuery)
    {
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {
            if ($key == "tags" || $key == "tagExclusive") {
                continue;
            }

            $this->applyComparison($key, $value, $finalQuery);
        }
    }

    /**
     * @param  array                &$criteria
     * @param  QueryBuilder         &$qb
     * @param  AuthorizationChecker|null &$authorizationChecker
     * @param  boolean $preview
     *
     * @return boolean Already Joined Node relation
     */
    protected function filterByAuthorizationChecker(
        &$criteria,
        &$qb,
        AuthorizationChecker &$authorizationChecker = null,
        $preview = false
    ) {
        $backendUser = $preview === true &&
        null !== $authorizationChecker &&
        $authorizationChecker->isGranted(Role::ROLE_BACKEND_USER);

        if ($backendUser) {
            /*
             * Forbid deleted node for backend user when authorizationChecker not null.
             */
            $qb->innerJoin('ns.node', 'n', 'WITH', $qb->expr()->lte('n.status', Node::PUBLISHED));
            return true;
        } elseif (null !== $authorizationChecker) {
            /*
             * Forbid unpublished node for anonymous and not backend users.
             */
            $qb->innerJoin('ns.node', 'n', 'WITH', $qb->expr()->eq('n.status', Node::PUBLISHED));
            return true;
        }

        return false;
    }

    /**
     * Create a securized query with node.published = true if user is
     * not a Backend user.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param AuthorizationChecker $authorizationChecker
     * @param boolean $preview
     * @return QueryBuilder
     */
    protected function getContextualQuery(
        array &$criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $joinedNodeType = false;
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'ns')
            ->add('from', $this->getEntityName() . ' ns');

        $joinedNode = $this->filterByAuthorizationChecker($criteria, $qb, $authorizationChecker, $preview);

        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb, $joinedNode);

        $this->filterByCriteria($criteria, $qb, $joinedNode, $joinedNodeType);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                if (false !== strpos($key, 'node.')) {
                    if (!$joinedNode) {
                        $qb->innerJoin('ns.node', 'n');
                    }
                    $simpleKey = str_replace('node.', '', $key);

                    $qb->addOrderBy('n.' . $simpleKey, $value);

                } else {
                    $qb->addOrderBy('ns.' . $key, $value);
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
     * Create a securized count query with node.published = true if user is
     * not a Backend user and if authorizationChecker is defined.
     *
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array                                   $criteria
     * @param AuthorizationChecker|null                    $authorizationChecker
     * @param boolean $preview
     *
     * @return QueryBuilder
     */
    protected function getCountContextualQueryWithTranslation(
        array &$criteria,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select($qb->expr()->countDistinct('ns.id'))
            ->add('from', $this->getEntityName() . ' ns');

        $joinedNode = $this->filterByAuthorizationChecker($criteria, $qb, $authorizationChecker, $preview);

        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb, $joinedNode);
        $this->filterByCriteria($criteria, $qb, $joinedNode);

        return $qb;
    }

    /**
     * Just like the countBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param AuthorizationChecker|null                    $authorizationChecker
     * @param boolean $preview
     *
     * @return int
     */
    public function countBy(
        $criteria,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $query = $this->getCountContextualQueryWithTranslation(
            $criteria,
            $authorizationChecker,
            $preview
        );

        $finalQuery = $query->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);

        try {
            return $finalQuery->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * A secure findBy with which user must be a backend user
     * to see unpublished nodes.
     *
     * Reimplementing findBy features… with extra things.
     *
     * * key => array('<=', $value)
     * * key => array('<', $value)
     * * key => array('>=', $value)
     * * key => array('>', $value)
     * * key => array('BETWEEN', $value, $value)
     * * key => array('LIKE', $value)
     * * key => array('NOT IN', $array)
     * * key => 'NOT NULL'
     *
     * You even can filter with node fields, examples:
     *
     * * `node.published => true`
     * * `node.nodeName => 'page1'`
     *
     * Or filter by tags:
     *
     * * `tags => $tag1`
     * * `tags => [$tag1, $tag2]`
     * * `tags => [$tag1, $tag2], tagExclusive => true`
     *
     * @param array           $criteria
     * @param array           $orderBy
     * @param integer         $limit
     * @param integer         $offset
     * @param AuthorizationChecker $authorizationChecker
     * @param boolean $preview
     *
     * @return ArrayCollection
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $qb = $this->getContextualQuery(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $authorizationChecker,
            $preview
        );

        $finalQuery = $qb->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        try {
            return $finalQuery->getResult();
        } catch (QueryException $e) {
            return null;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * A secure findOneBy with which user must be a backend user
     * to see unpublished nodes.
     *
     *
     * @param array           $criteria
     * @param array           $orderBy
     * @param AuthorizationChecker $authorizationChecker
     * @param boolean $preview
     *
     * @return NodesSources|null
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $qb = $this->getContextualQuery(
            $criteria,
            $orderBy,
            1,
            null,
            $authorizationChecker,
            $preview
        );

        $finalQuery = $qb->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);

        try {
            return $finalQuery->getSingleResult();
        } catch (QueryException $e) {
            return null;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Search nodes sources by using Solr search engine.
     *
     * @param string $query Solr query string (for example: `text:Lorem Ipsum`)
     * @param integer $limit Result number to fetch (default: all)
     *
     * @return ArrayCollection | null
     */
    public function findBySearchQuery($query, $limit = 0)
    {
        // Update Solr Serach engine if setup
        if (true === Kernel::getService('solr.ready')) {
            $service = Kernel::getService('solr');

            $queryObj = $service->createSelect();

            $queryObj->setQuery('collection_txt:' . $query);
            $queryObj->addSort('score', $queryObj::SORT_DESC);

            if ($limit > 0) {
                $queryObj->setRows((int) $limit);
            }

            // this executes the query and returns the result
            $resultset = $service->select($queryObj);

            if (0 === $resultset->getNumFound()) {
                return null;
            } else {
                $sources = new ArrayCollection();

                foreach ($resultset as $document) {
                    $sources->add($this->_em->find(
                        'RZ\Roadiz\Core\Entities\NodesSources',
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
        if (true === Kernel::getService('solr.ready')) {
            $service = Kernel::getService('solr');

            $queryObj = $service->createSelect();

            $queryObj->setQuery('collection_txt:' . $query);
            // create a filterquery
            $queryObj->createFilterQuery('translation')->setQuery('locale_s:' . $translation->getLocale());
            $queryObj->addSort('score', $queryObj::SORT_DESC);

            // this executes the query and returns the result
            $resultset = $service->select($queryObj);

            if (0 === $resultset->getNumFound()) {
                return null;
            } else {
                $sources = new ArrayCollection();

                foreach ($resultset as $document) {
                    $sources->add($this->_em->find(
                        'RZ\Roadiz\Core\Entities\NodesSources',
                        $document['node_source_id_i']
                    ));
                }

                return $sources;
            }
        }

        return null;
    }

    /**
     * Find latest updated NodesSources using Log table.
     *
     * @param integer $maxResult
     *
     * @return array|null
     */
    public function findByLatestUpdated($maxResult = 5)
    {
        $query = $this->createQueryBuilder('ns');
        $query->select('ns');
        $query->addSelect('log');
        $query->innerJoin('ns.logs', 'log');
        $query->setMaxResults($maxResult);
        $query->orderBy('log.datetime', 'DESC');
        /*
         * Cannot groupBy for the moment due to an incompatibility with Doctrine
         * http://www.doctrine-project.org/jira/browse/DDC-2917
         */
        $query = $query->getQuery();

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Get node-source parent according to its translation.
     *
     * @param  NodesSources $nodeSource
     * @return NodesSources|null
     */
    public function findParent(NodesSources $nodeSource)
    {
        if (null !== $nodeSource->getNode()->getParent()) {
            try {
                $query = $this->_em->createQuery('
                    SELECT ns FROM RZ\Roadiz\Core\Entities\NodesSources ns
                    WHERE ns.node = :node
                    AND ns.translation = :translation')
                    ->setParameter('node', $nodeSource->getNode()->getParent())
                    ->setParameter('translation', $nodeSource->getTranslation());

                return $query->getSingleResult();
            } catch (NoResultException $e) {
                return null;
            }
        } else {
            return null;
        }
    }
}
