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

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

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
     * @param boolean      $joinedNode
     */
    protected function filterByTag(&$criteria, &$qb, &$joinedNode)
    {
        if (in_array('tags', array_keys($criteria))) {
            if (!$joinedNode) {
                $qb->innerJoin(
                    'ns.node',
                    static::NODE_ALIAS
                );
                $joinedNode = true;
            }

            $this->buildTagFiltering($criteria, $qb);
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
            $prefix = static::NODESSOURCES_ALIAS . '.';

            // Dots are forbidden in field definitions
            $baseKey = str_replace('.', '_', $key);

            if (false !== strpos($key, 'node.nodeType.')) {
                if (!$joinedNode) {
                    $qb->innerJoin(
                        'ns.node',
                        static::NODE_ALIAS
                    );
                    $joinedNode = true;
                }
                if (!$joinedNodeType) {
                    $qb->addSelect('nt');
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
                        static::NODE_ALIAS
                    );
                    $joinedNode = true;
                }

                $prefix = static::NODE_ALIAS . '.';
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
                $qb->innerJoin($alias . '.node', static::NODE_ALIAS);
            }

            $prefix = static::NODE_ALIAS;
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
     * @param Query $finalQuery
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
     * @param array                     $criteria
     * @param QueryBuilder              $qb
     * @param AuthorizationChecker|null $authorizationChecker
     * @param bool                      $preview
     *
     * @return bool
     */
    protected function filterByAuthorizationChecker(
        &$criteria,
        &$qb,
        AuthorizationChecker &$authorizationChecker = null,
        $preview = false
    ) {
        $backendUser = $this->isBackendUser();

        if ($backendUser) {
            /*
             * Forbid deleted node for backend user when authorizationChecker not null.
             */
            $qb->innerJoin('ns.node', static::NODE_ALIAS, 'WITH', $qb->expr()->lte(static::NODE_ALIAS . '.status', Node::PUBLISHED));
            return true;
        } elseif (null !== $authorizationChecker) {
            /*
             * Forbid unpublished node for anonymous and not backend users.
             */
            $qb->innerJoin('ns.node', static::NODE_ALIAS, 'WITH', $qb->expr()->eq(static::NODE_ALIAS . '.status', Node::PUBLISHED));
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
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $joinedNode = $this->filterByAuthorizationChecker($criteria, $qb, $authorizationChecker, $preview);

        if (!$joinedNode) {
            $qb->innerJoin('ns.node', static::NODE_ALIAS);
            $joinedNode = true;
        }
        $qb->addSelect(static::NODE_ALIAS);

        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb, $joinedNode);
        $this->filterByCriteria($criteria, $qb, $joinedNode, $joinedNodeType);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                if (false !== strpos($key, 'node.')) {
                    $simpleKey = str_replace('node.', '', $key);
                    $qb->addOrderBy(static::NODE_ALIAS . '.' . $simpleKey, $value);
                } else {
                    $qb->addOrderBy(static::NODESSOURCES_ALIAS . '.' . $key, $value);
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
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $qb->select($qb->expr()->countDistinct('ns.id'));
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
     * @param array $criteria
     * @param AuthorizationChecker|null $authorizationChecker
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
            return (int) $finalQuery->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
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
     * @return array|Paginator
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

        /*
         * Eagerly fetch UrlAliases
         * to limit SQL query count
         */
        $qb->leftJoin('ns.urlAliases', 'ua')
            ->addSelect('ua')
        ;

        $qb->setCacheable(true);
        $finalQuery = $qb->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);

        if (null !== $limit &&
            null !== $offset) {
            /*
             * We need to use Doctrine paginator
             * if a limit is set because of the default inner join
             */
            return new Paginator($finalQuery);
        } else {
            try {
                return $finalQuery->getResult();
            } catch (NoResultException $e) {
                return [];
            }
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

        /*
         * Eagerly fetch UrlAliases
         * to limit SQL query count
         */
        $qb->leftJoin('ns.urlAliases', 'ua')
            ->addSelect('ua')
        ;

        $qb->setCacheable(true);
        $finalQuery = $qb->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);

        try {
            return $finalQuery->getSingleResult();
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
     * @return array
     */
    public function findBySearchQuery($query, $limit = 25)
    {
        if (true === $this->get('solr.ready')) {
            /** @var NodeSourceSearchHandler $service */
            $service = $this->get('solr.search.nodeSource');

            if ($limit > 0) {
                return $service->search($query, [], $limit);
            } else {
                return $service->search($query, [], 999999);
            }
        }
        return [];
    }

    /**
     * Search nodes sources by using Solr search engine
     * and a specific translation.
     *
     * @param string $query Solr query string (for example: `text:Lorem Ipsum`)
     * @param Translation $translation Current translation
     *
     * @param int $limit
     * @return array
     */
    public function findBySearchQueryAndTranslation($query, Translation $translation, $limit = 25)
    {
        if (true === $this->get('solr.ready')) {
            /** @var NodeSourceSearchHandler $service */
            $service = $this->get('solr.search.nodeSource');
            $params = [
                'translation' => $translation,
            ];

            if ($limit > 0) {
                return $service->search($query, $params, $limit);
            } else {
                return $service->search($query, $params, 999999);
            }
        }
        return [];
    }

    /**
     * Search Nodes-Sources using LIKE condition on title
     * meta-title, meta-keywords, meta-description.
     *
     * @param $textQuery
     * @param int $limit
     * @param array $nodeTypes
     * @param bool $onlyVisible
     * @param AuthorizationChecker $authorizationChecker
     * @param bool $preview
     * @return array
     */
    public function findByTextQuery(
        $textQuery,
        $limit = 0,
        $nodeTypes = [],
        $onlyVisible = false,
        AuthorizationChecker &$authorizationChecker = null,
        $preview = false
    ) {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $qb->select('ns, n, ua')
            ->leftJoin('ns.urlAliases', 'ua')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->like('ns.title', ':query'),
                $qb->expr()->like('ns.metaTitle', ':query'),
                $qb->expr()->like('ns.metaKeywords', ':query'),
                $qb->expr()->like('ns.metaDescription', ':query')
            ))
            ->orderBy('ns.title', 'ASC')
            ->setParameter(':query', '%' . $textQuery . '%');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        $criteria = [];

        if (false === $this->filterByAuthorizationChecker($criteria, $qb, $authorizationChecker, $preview)) {
            $qb->innerJoin('ns.node', static::NODE_ALIAS);
        }

        if (count($nodeTypes) > 0) {
            $qb->andWhere($qb->expr()->in('n.nodeType', ':types'))
                ->setParameter(':types', $nodeTypes);
        }

        if (true === $onlyVisible) {
            $qb->andWhere($qb->expr()->eq('n.visible', ':visible'))
                ->setParameter(':visible', true);
        }

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Find latest updated NodesSources using Log table.
     *
     * @param integer $maxResult
     *
     * @return Paginator
     */
    public function findByLatestUpdated($maxResult = 5)
    {
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('sns.id')
                 ->from('RZ\Roadiz\Core\Entities\Log', 'slog')
                 ->innerJoin('RZ\Roadiz\Core\Entities\NodesSources', 'sns')
                 ->andWhere($subQuery->expr()->isNotNull('slog.nodeSource'))
                 ->orderBy('slog.datetime', 'DESC');

        $query = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $query->andWhere($query->expr()->in('ns.id', $subQuery->getQuery()->getDQL()));
        $query->setMaxResults($maxResult);

        return new Paginator($query->getQuery());
    }

    /**
     * Get node-source parent according to its translation.
     *
     * @param  NodesSources $nodeSource
     * @return NodesSources|null
     */
    public function findParent(NodesSources $nodeSource)
    {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $qb->select('ns, n, ua')
            ->innerJoin('ns.node', static::NODE_ALIAS)
            ->innerJoin('n.children', 'cn')
            ->leftJoin('ns.urlAliases', 'ua')
            ->andWhere($qb->expr()->eq('cn.id', ':childNodeId'))
            ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
            ->setParameter('childNodeId', $nodeSource->getNode()->getId())
            ->setParameter('translation', $nodeSource->getTranslation())
            ->setMaxResults(1)
            ->setCacheable(true);

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Node $node
     * @param Translation $translation
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByNodeAndTranslation(Node $node, Translation $translation)
    {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);

        $qb->select(static::NODESSOURCES_ALIAS)
            ->andWhere($qb->expr()->eq('ns.node', ':node'))
            ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
            ->setMaxResults(1)
            ->setParameter('node', $node)
            ->setParameter('translation', $translation)
            ->setCacheable(true);

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     *
     * Extends EntityRepository to make join possible with «node.» prefix.
     * Required if making search with EntityListManager and filtering by node criteria.
     */
    protected function prepareComparisons(array &$criteria, QueryBuilder $qb, $alias)
    {
        foreach ($criteria as $key => $value) {
            $baseKey = str_replace('.', '_', $key);

            if (false !== strpos($key, 'node.')) {
                if (!$this->hasJoinedNode($qb, $alias)) {
                    $qb->innerJoin($alias . '.node', static::NODE_ALIAS);
                }
                $simpleKey = str_replace('node.', '', $key);
                $qb->andWhere($this->buildComparison($value, static::NODE_ALIAS . '.', $simpleKey, $baseKey, $qb));
            } else {
                $qb->andWhere($this->buildComparison($value, $alias . '.', $key, $baseKey, $qb));
            }
        }

        return $qb;
    }
}
