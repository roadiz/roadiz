<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesApplyEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesBuildEvent;
use RZ\Roadiz\Core\Events\QueryNodesSourcesEvent;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandler;
use RZ\Roadiz\Core\SearchEngine\SolrSearchResults;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * EntityRepository that implements search engine query with Solr.
 * @extends StatusAwareRepository<\RZ\Roadiz\Core\Entities\NodesSources>
 */
class NodesSourcesRepository extends StatusAwareRepository
{
    /**
     * @param QueryBuilder $qb
     * @param string $property
     * @param mixed $value
     *
     * @return object|QueryBuilderNodesSourcesBuildEvent
     */
    protected function dispatchQueryBuilderBuildEvent(QueryBuilder $qb, $property, $value)
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->container['dispatcher'];
        return $eventDispatcher->dispatch(
            new QueryBuilderNodesSourcesBuildEvent($qb, $property, $value, $this->getEntityName())
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param string $property
     * @param mixed $value
     *
     * @return object|QueryBuilderNodesSourcesApplyEvent
     */
    protected function dispatchQueryBuilderApplyEvent(QueryBuilder $qb, $property, $value)
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->container['dispatcher'];
        return $eventDispatcher->dispatch(
            new QueryBuilderNodesSourcesApplyEvent($qb, $property, $value, $this->getEntityName())
        );
    }

    /**
     * @param Query  $query
     *
     * @return object|QueryNodesSourcesEvent
     */
    protected function dispatchQueryEvent(Query $query)
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->container['dispatcher'];
        return $eventDispatcher->dispatch(
            new QueryNodesSourcesEvent($query, $this->getEntityName())
        );
    }

    /**
     * Add a tag filtering to queryBuilder.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByTag(array &$criteria, QueryBuilder $qb)
    {
        if (key_exists('tags', $criteria)) {
            if (!$this->hasJoinedNode($qb, static::NODESSOURCES_ALIAS)) {
                $qb->innerJoin(
                    static::NODESSOURCES_ALIAS . '.node',
                    static::NODE_ALIAS
                );
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
     */
    protected function filterByCriteria(
        array &$criteria,
        QueryBuilder $qb
    ) {
        $simpleQB = new SimpleQueryBuilder($qb);
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {
            if ($key == "tags" || $key == "tagExclusive") {
                continue;
            }
            /*
             * Main QueryBuilder dispatch loop for
             * custom properties criteria.
             */
            $event = $this->dispatchQueryBuilderBuildEvent($qb, $key, $value);

            if (!$event->isPropagationStopped()) {
                /*
                 * compute prefix for
                 * filtering node relation fields
                 */
                $prefix = static::NODESSOURCES_ALIAS . '.';
                // Dots are forbidden in field definitions
                $baseKey = $simpleQB->getParameterKey($key);
                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($value, $prefix, $key, $baseKey));
            }
        }
    }

    /**
     * Bind parameters to generated query.
     *
     * @param array $criteria
     * @param QueryBuilder $qb
     */
    protected function applyFilterByCriteria(array &$criteria, QueryBuilder $qb)
    {
        /*
         * Reimplementing findBy features…
         */
        $simpleQB = new SimpleQueryBuilder($qb);
        foreach ($criteria as $key => $value) {
            if ($key == "tags" || $key == "tagExclusive") {
                continue;
            }

            $event = $this->dispatchQueryBuilderApplyEvent($qb, $key, $value);
            if (!$event->isPropagationStopped()) {
                $simpleQB->bindValue($key, $value);
            }
        }
    }


    /**
     * @param QueryBuilder $qb
     * @param string $prefix
     * @return QueryBuilder
     */
    protected function alterQueryBuilderWithAuthorizationChecker(
        QueryBuilder $qb,
        $prefix = EntityRepository::NODESSOURCES_ALIAS
    ) {
        if (true === $this->isDisplayingAllNodesStatuses()) {
            if (!$this->hasJoinedNode($qb, $prefix)) {
                $qb->innerJoin($prefix . '.node', static::NODE_ALIAS);
            }
            return $qb;
        }

        if (true === $this->isDisplayingNotPublishedNodes() || $this->isBackendUserWithPreview()) {
            /*
             * Forbid deleted node for backend user when authorizationChecker not null.
             */
            if (!$this->hasJoinedNode($qb, $prefix)) {
                $qb->innerJoin(
                    $prefix . '.node',
                    static::NODE_ALIAS,
                    'WITH',
                    $qb->expr()->lte(static::NODE_ALIAS . '.status', Node::PUBLISHED)
                );
            } else {
                $qb->andWhere($qb->expr()->lte(static::NODE_ALIAS . '.status', Node::PUBLISHED));
            }
        } else {
            /*
             * Forbid unpublished node for anonymous and not backend users.
             */
            if (!$this->hasJoinedNode($qb, $prefix)) {
                $qb->innerJoin(
                    $prefix . '.node',
                    static::NODE_ALIAS,
                    'WITH',
                    $qb->expr()->eq(static::NODE_ALIAS . '.status', Node::PUBLISHED)
                );
            } else {
                $qb->andWhere($qb->expr()->eq(static::NODE_ALIAS . '.status', Node::PUBLISHED));
            }
        }
        return $qb;
    }

    /**
     * Create a secure query with node.published = true if user is
     * not a Backend user.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return QueryBuilder
     */
    protected function getContextualQuery(
        array &$criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $this->alterQueryBuilderWithAuthorizationChecker($qb, static::NODESSOURCES_ALIAS);
        $qb->addSelect(static::NODE_ALIAS);
        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb);
        $this->filterByCriteria($criteria, $qb);

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
     * Create a secured count query with node.published = true if user is
     * not a Backend user and if authorizationChecker is defined.
     *
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array $criteria
     * @return QueryBuilder
     */
    protected function getCountContextualQuery(array &$criteria)
    {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $this->alterQueryBuilderWithAuthorizationChecker($qb, static::NODESSOURCES_ALIAS);
        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb);
        $this->filterByCriteria($criteria, $qb);

        return $qb->select($qb->expr()->countDistinct(static::NODESSOURCES_ALIAS . '.id'));
    }

    /**
     * Just like the countBy method but with relational criteria.
     *
     * @param array $criteria
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countBy($criteria)
    {
        $query = $this->getCountContextualQuery($criteria);
        $this->dispatchQueryBuilderEvent($query, $this->getEntityName());
        $this->applyFilterByTag($criteria, $query);
        $this->applyFilterByCriteria($criteria, $query);

        return (int) $query->getQuery()->getSingleScalarResult();
    }

    /**
     * A secure findBy with which user must be a backend user
     * to see unpublished nodes.
     *
     * Reimplementing findBy features with extra things.
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
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array|Paginator
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {
        $qb = $this->getContextualQuery(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
        /*
         * Eagerly fetch UrlAliases
         * to limit SQL query count
         */
        $qb->leftJoin(static::NODESSOURCES_ALIAS . '.urlAliases', 'ua')
            ->addSelect('ua')
        ;
        $qb->setCacheable(true);
        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByTag($criteria, $qb);
        $this->applyFilterByCriteria($criteria, $qb);
        $query = $qb->getQuery();
        $this->dispatchQueryEvent($query);

        if (null !== $limit &&
            null !== $offset) {
            /*
             * We need to use Doctrine paginator
             * if a limit is set because of the default inner join
             */
            return new Paginator($query);
        } else {
            return $query->getResult();
        }
    }

    /**
     * A secure findOneBy with which user must be a backend user
     * to see unpublished nodes.
     *
     * @param array $criteria
     * @param array $orderBy
     *
     * @return null|NodesSources
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null
    ) {
        $qb = $this->getContextualQuery(
            $criteria,
            $orderBy,
            1,
            null
        );

        /*
         * Eagerly fetch UrlAliases
         * to limit SQL query count
         */
        $qb->leftJoin(static::NODESSOURCES_ALIAS . '.urlAliases', 'ua')
            ->addSelect('ua')
        ;
        $qb->setCacheable(true);
        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByTag($criteria, $qb);
        $this->applyFilterByCriteria($criteria, $qb);
        $query = $qb->getQuery();
        $this->dispatchQueryEvent($query);

        return $query->getOneOrNullResult();
    }

    /**
     * Search nodes sources by using Solr search engine.
     *
     * @param string $query Solr query string (for example: `text:Lorem Ipsum`)
     * @param integer $limit Result number to fetch (default: all)
     * @return array
     */
    public function findBySearchQuery($query, $limit = 25)
    {
        /** @var NodeSourceSearchHandler|null $service */
        $service = $this->get('solr.search.nodeSource');
        if (null !== $service) {
            $service->boostByUpdateDate();
            $arguments = [];
            if ($this->isDisplayingNotPublishedNodes()) {
                $arguments['status'] = ['<=', Node::PUBLISHED];
            }
            if ($this->isDisplayingAllNodesStatuses()) {
                $arguments['status'] = ['<=', Node::DELETED];
            }

            if ($limit > 0) {
                return $service->search($query, $arguments, $limit)->getResultItems();
            }
            return $service->search($query, $arguments, 999999)->getResultItems();
        }
        return [];
    }

    /**
     * Search nodes sources by using Solr search engine
     * and a specific translation.
     *
     * @param string $query Solr query string (for example: `text:Lorem Ipsum`)
     * @param Translation $translation Current translation
     * @param int $limit
     * @return SolrSearchResults
     */
    public function findBySearchQueryAndTranslation($query, Translation $translation, $limit = 25)
    {
        /** @var NodeSourceSearchHandler|null $service */
        $service = $this->get('solr.search.nodeSource');
        if (null !== $service) {
            $params = [
                'translation' => $translation,
            ];

            if ($limit > 0) {
                return $service->search($query, $params, $limit);
            } else {
                return $service->search($query, $params, 999999);
            }
        }
        return new SolrSearchResults([], $this->_em);
    }

    /**
     * Search Nodes-Sources using LIKE condition on title
     * meta-title, meta-keywords, meta-description.
     *
     * @param string $textQuery
     * @param int $limit
     * @param array $nodeTypes
     * @param bool $onlyVisible
     * @param array $additionalCriteria
     * @return array
     */
    public function findByTextQuery(
        $textQuery,
        int $limit = 0,
        array $nodeTypes = [],
        bool $onlyVisible = false,
        array $additionalCriteria = []
    ) {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $qb->addSelect(static::NODE_ALIAS)
            ->addSelect('ua')
            ->leftJoin(static::NODESSOURCES_ALIAS . '.urlAliases', 'ua')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->like(static::NODESSOURCES_ALIAS . '.title', ':query'),
                $qb->expr()->like(static::NODESSOURCES_ALIAS . '.metaTitle', ':query'),
                $qb->expr()->like(static::NODESSOURCES_ALIAS . '.metaKeywords', ':query'),
                $qb->expr()->like(static::NODESSOURCES_ALIAS . '.metaDescription', ':query')
            ))
            ->orderBy(static::NODESSOURCES_ALIAS . '.title', 'ASC')
            ->setParameter(':query', '%' . $textQuery . '%');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        /*
         * Alteration always join node table.
         */
        $this->alterQueryBuilderWithAuthorizationChecker($qb, static::NODESSOURCES_ALIAS);

        if (count($nodeTypes) > 0) {
            $additionalCriteria['node.nodeType'] = $nodeTypes;
        }

        if (true === $onlyVisible) {
            $additionalCriteria['node.visible'] = true;
        }

        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());

        if (count($additionalCriteria) > 0) {
            $this->prepareComparisons($additionalCriteria, $qb, static::NODESSOURCES_ALIAS);
            $this->applyFilterByCriteria($additionalCriteria, $qb);
        }

        $query = $qb->getQuery();
        $this->dispatchQueryEvent($query);

        return $query->getResult();
    }

    /**
     * Find latest updated NodesSources using Log table.
     *
     * @param integer $maxResult
     * @return Paginator
     */
    public function findByLatestUpdated($maxResult = 5)
    {
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('sns.id')
                 ->from(Log::class, 'slog')
                 ->innerJoin(NodesSources::class, 'sns')
                 ->andWhere($subQuery->expr()->isNotNull('slog.nodeSource'))
                 ->orderBy('slog.datetime', 'DESC');

        $query = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $query->andWhere($query->expr()->in(static::NODESSOURCES_ALIAS . '.id', $subQuery->getQuery()->getDQL()));
        $query->setMaxResults($maxResult);

        return new Paginator($query->getQuery());
    }

    /**
     * Get node-source parent according to its translation.
     *
     * @param NodesSources $nodeSource
     *
     * @return NodesSources|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findParent(NodesSources $nodeSource)
    {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $qb->select(static::NODESSOURCES_ALIAS . ', n, ua')
            ->innerJoin(static::NODESSOURCES_ALIAS . '.node', static::NODE_ALIAS)
            ->innerJoin('n.children', 'cn')
            ->leftJoin(static::NODESSOURCES_ALIAS . '.urlAliases', 'ua')
            ->andWhere($qb->expr()->eq('cn.id', ':childNodeId'))
            ->andWhere($qb->expr()->eq(static::NODESSOURCES_ALIAS . '.translation', ':translation'))
            ->setParameter('childNodeId', $nodeSource->getNode()->getId())
            ->setParameter('translation', $nodeSource->getTranslation())
            ->setMaxResults(1)
            ->setCacheable(true);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Node $node
     * @param Translation $translation
     * @return mixed|null
     */
    public function findOneByNodeAndTranslation(Node $node, Translation $translation)
    {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);

        $qb->select(static::NODESSOURCES_ALIAS)
            ->andWhere($qb->expr()->eq(static::NODESSOURCES_ALIAS . '.node', ':node'))
            ->andWhere($qb->expr()->eq(static::NODESSOURCES_ALIAS . '.translation', ':translation'))
            ->setMaxResults(1)
            ->setParameter('node', $node)
            ->setParameter('translation', $translation)
            ->setCacheable(true);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @inheritdoc
     *
     * Extends EntityRepository to make join possible with «node.» prefix.
     * Required if making search with EntityListManager and filtering by node criteria.
     */
    protected function prepareComparisons(array &$criteria, QueryBuilder $qb, $alias)
    {
        $simpleQB = new SimpleQueryBuilder($qb);

        foreach ($criteria as $key => $value) {
            /*
             * Main QueryBuilder dispatch loop for
             * custom properties criteria.
             */
            $event = $this->dispatchQueryBuilderBuildEvent($qb, $key, $value);

            if (!$event->isPropagationStopped()) {
                $baseKey = $simpleQB->getParameterKey($key);
                if (false !== strpos($key, 'node.nodeType.')) {
                    if (!$this->hasJoinedNode($qb, $alias)) {
                        $qb->innerJoin($alias . '.node', static::NODE_ALIAS);
                    }
                    if (!$this->hasJoinedNodeType($qb, $alias)) {
                        $qb->innerJoin(static::NODE_ALIAS . '.nodeType', static::NODETYPE_ALIAS);
                    }
                    $prefix = static::NODETYPE_ALIAS . '.';
                    $simpleKey = str_replace('node.nodeType.', '', $key);
                    $qb->andWhere($simpleQB->buildExpressionWithoutBinding($value, $prefix, $simpleKey, $baseKey));
                } elseif (false !== strpos($key, 'node.')) {
                    if (!$this->hasJoinedNode($qb, $alias)) {
                        $qb->innerJoin($alias . '.node', static::NODE_ALIAS);
                    }
                    $prefix = static::NODE_ALIAS . '.';
                    $simpleKey = str_replace('node.', '', $key);
                    $qb->andWhere($simpleQB->buildExpressionWithoutBinding($value, $prefix, $simpleKey, $baseKey));
                } else {
                    $qb->andWhere($simpleQB->buildExpressionWithoutBinding($value, $alias . '.', $key, $baseKey));
                }
            }
        }

        return $qb;
    }

    /**
     * @inheritDoc
     */
    protected function createSearchBy(
        $pattern,
        QueryBuilder $qb,
        array &$criteria = [],
        $alias = EntityRepository::DEFAULT_ALIAS
    ) {
        $qb = parent::createSearchBy($pattern, $qb, $criteria, $alias);
        $this->alterQueryBuilderWithAuthorizationChecker($qb, $alias);

        return $qb;
    }

    /**
     * @inheritDoc
     */
    public function searchBy(
        $pattern,
        array $criteria = [],
        array $orders = [],
        $limit = null,
        $offset = null,
        $alias = EntityRepository::DEFAULT_ALIAS
    ) {
        return parent::searchBy($pattern, $criteria, $orders, $limit, $offset, static::NODESSOURCES_ALIAS);
    }

    /**
     * @param NodesSources  $nodesSources
     * @param NodeTypeField $field
     *
     * @return array|null
     */
    public function findByNodesSourcesAndFieldAndTranslation(
        NodesSources $nodesSources,
        NodeTypeField $field
    ) {
        $qb = $this->createQueryBuilder(static::NODESSOURCES_ALIAS);
        $qb->select('ns, n, ua')
            ->innerJoin('ns.node', static::NODE_ALIAS)
            ->leftJoin('ns.urlAliases', 'ua')
            ->innerJoin('n.aNodes', 'ntn')
            ->andWhere($qb->expr()->eq('ntn.field', ':field'))
            ->andWhere($qb->expr()->eq('ntn.nodeA', ':nodeA'))
            ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
            ->addOrderBy('ntn.position', 'ASC')
            ->setCacheable(true);

        $this->alterQueryBuilderWithAuthorizationChecker($qb);

        $qb->setParameter('field', $field)
            ->setParameter('nodeA', $nodesSources->getNode())
            ->setParameter('translation', $nodesSources->getTranslation());

        return $qb->getQuery()->getResult();
    }
}
