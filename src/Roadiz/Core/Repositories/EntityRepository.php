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
 * @file EntityRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Pimple\Container;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\FilterQueryBuilderCriteriaEvent;
use RZ\Roadiz\Core\Events\FilterQueryBuilderEvent;
use RZ\Roadiz\Core\Events\QueryBuilderEvents;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * EntityRepository that implements a simple countBy method.
 */
class EntityRepository extends \Doctrine\ORM\EntityRepository implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    protected $isPreview;

    /**
     * EntityRepository constructor.
     * @param EntityManager $em
     * @param Mapping\ClassMetadata $class
     * @param Container $container
     * @param bool $isPreview
     */
    public function __construct(
        EntityManager $em,
        Mapping\ClassMetadata $class,
        Container $container,
        $isPreview = false
    ) {
        parent::__construct($em, $class);
        $this->isPreview = $isPreview;
        $this->container = $container;
    }

    /**
     * Alias for DQL and Query builder representing Node relation.
     */
    const DEFAULT_ALIAS = 'obj';

    /**
     * Alias for DQL and Query builder representing Node relation.
     */
    const NODE_ALIAS = 'n';

    /**
     * Alias for DQL and Query builder representing NodesSources relation.
     */
    const NODESSOURCES_ALIAS = 'ns';

    /**
     * Alias for DQL and Query builder representing Translation relation.
     */
    const TRANSLATION_ALIAS = 't';

    /**
     * Alias for DQL and Query builder representing Tag relation.
     */
    const TAG_ALIAS = 'tg';

    /**
     * Alias for DQL and Query builder representing NodeType relation.
     */
    const NODETYPE_ALIAS = 'nt';

    /**
     * Doctrine column types that can be search
     * with LIKE feature.
     *
     * @var array
     */
    protected $searchableTypes = ['string', 'text'];

    /**
     * @param QueryBuilder $qb
     * @param string $entityClass
     */
    protected function dispatchQueryBuilderEvent(QueryBuilder $qb, $entityClass)
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->container['dispatcher'];
        $initialQueryBuilderEvent = new FilterQueryBuilderEvent($qb, $entityClass);
        $eventDispatcher->dispatch(QueryBuilderEvents::QUERY_BUILDER_SELECT, $initialQueryBuilderEvent);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $property
     * @param mixed $value
     *
     * @return FilterQueryBuilderCriteriaEvent
     */
    protected function dispatchQueryBuilderBuildEvent(QueryBuilder $qb, $property, $value)
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->container['dispatcher'];
        $event = new FilterQueryBuilderCriteriaEvent($qb, $this->getEntityName(), $property, $value, $this->getEntityName());
        $eventDispatcher->dispatch(QueryBuilderEvents::QUERY_BUILDER_BUILD_FILTER, $event);

        return $event;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $property
     * @param mixed $value
     *
     * @return FilterQueryBuilderCriteriaEvent
     */
    protected function dispatchQueryBuilderApplyEvent(QueryBuilder $qb, $property, $value)
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->container['dispatcher'];
        $event = new FilterQueryBuilderCriteriaEvent($qb, $this->getEntityName(), $property, $value, $this->getEntityName());
        $eventDispatcher->dispatch(QueryBuilderEvents::QUERY_BUILDER_APPLY_FILTER, $event);

        return $event;
    }

    /**
     * Build a query comparison.
     *
     * @param mixed $value
     * @param string $prefix The prefix should always end with a dot
     * @param string $key
     * @param string $baseKey
     * @param QueryBuilder $qb
     *
     * @return string
     * @deprecated Use SimpleQueryBuilder::buildExpressionWithoutBinding
     */
    protected function buildComparison($value, $prefix, $key, $baseKey, QueryBuilder $qb)
    {
        $simpleQB = new SimpleQueryBuilder($qb);
        $baseKey = $simpleQB->getParameterKey($baseKey);
        return $simpleQB->buildExpressionWithoutBinding($value, $prefix, $key, $baseKey);
    }

    /**
     * Direct bind parameters without preparation.
     *
     * @param array $criteria
     * @param QueryBuilder $qb
     * @param string $prefix Property prefix including DOT
     *
     * @return QueryBuilder
     * @deprecated Use findBy or manual QueryBuilder methods
     */
    protected function directComparison(array &$criteria, QueryBuilder $qb, $prefix)
    {
        $simpleQB = new SimpleQueryBuilder($qb);
        foreach ($criteria as $key => $value) {
            $qb = $simpleQB->buildExpressionWithBinding($value, $prefix, $key);
        }

        return $qb;
    }

    /**
     *
     * @param  array        $criteria
     * @param  QueryBuilder $qb
     * @param  string       $alias
     * @return QueryBuilder
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
                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($value, $alias . '.', $key));
            }
        }

        return $qb;
    }

    /**
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function applyFilterByCriteria(array &$criteria, QueryBuilder $qb)
    {
        $simpleQB = new SimpleQueryBuilder($qb);
        foreach ($criteria as $key => $value) {
            $event = $this->dispatchQueryBuilderApplyEvent($qb, $key, $value);
            if (!$event->isPropagationStopped()) {
                $simpleQB->bindValue($key, $value);
            }
        }
    }

    /**
     * @param array $criteria
     * @param Query $finalQuery
     * @deprecated
     */
    protected function applyComparisons(array &$criteria, Query $finalQuery)
    {
        foreach ($criteria as $key => $value) {
            $this->applyComparison($key, $value, $finalQuery);
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
     * @deprecated Use SimpleQueryBuilder::buildExpressionWithBinding
     */
    protected function singleDirectComparison($key, &$value, QueryBuilder $qb, $alias)
    {
        if ($value instanceof PersistableInterface) {
            $res = $qb->expr()->eq($alias . '.' . $key, $value->getId());
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
                    case '!=':
                        # neq
                        $res = $qb->expr()->neq($alias . '.' . $key, $value[1]);
                        break;
                    case '<=':
                        # lte
                        $res = $qb->expr()->lte($alias . '.' . $key, $value[1]);
                        break;
                    case '<':
                        # lt
                        $res = $qb->expr()->lt($alias . '.' . $key, $value[1]);
                        break;
                    case '>=':
                        # gte
                        $res = $qb->expr()->gte($alias . '.' . $key, $value[1]);
                        break;
                    case '>':
                        # gt
                        $res = $qb->expr()->gt($alias . '.' . $key, $value[1]);
                        break;
                    case 'BETWEEN':
                        $res = $qb->expr()->between(
                            $alias . '.' . $key,
                            $value[1],
                            $value[2]
                        );
                        break;
                    case 'LIKE':
                        $fullKey = sprintf('LOWER(%s)', $alias . '.' . $key);
                        $res = $qb->expr()->like($fullKey, $qb->expr()->literal(strtolower($value[1])));
                        break;
                    case 'INSTANCE OF':
                        $res = $qb->expr()->isInstanceOf($alias . '.' . $key, $value[1]);
                        break;
                    default:
                        $res = $this->directExprIn($qb, $alias . '.' . $key, $key, $value);
                        break;
                }
            } else {
                $res = $this->directExprIn($qb, $alias . '.' . $key, $key, $value);
            }
        } elseif (is_bool($value)) {
            $res = $qb->expr()->eq($alias . '.' . $key, (boolean) $value);
        } else {
            $res = $qb->expr()->eq($alias . '.' . $key, $value);
        }

        $qb->andWhere($res);

        return $qb;
    }

    /**
     * @param  QueryBuilder &$qb
     * @param  string $name
     * @param  string $key
     * @param  array $value
     *
     * @return Query\Expr\Func
     */
    protected function directExprIn(QueryBuilder $qb, $name, $key, $value)
    {
        $newValue = [];

        if (is_array($value)) {
            foreach ($value as $singleValue) {
                if ($singleValue instanceof PersistableInterface) {
                    $newValue[] = $singleValue->getId();
                } else {
                    $newValue[] = $value;
                }
            }
        }

        return $qb->expr()->in($name, $newValue);
    }

    /**
     * Bind classic parameters to your query.
     *
     * @param string $key
     * @param mixed  $value
     * @param Query  $finalQuery
     * @deprecated Use SimpleQueryBuilder::bindValue
     */
    protected function applyComparison($key, $value, Query $finalQuery)
    {
        $key = str_replace('.', '_', $key);

        if ($value instanceof PersistableInterface) {
            $finalQuery->setParameter($key, $value->getId());
        } elseif (is_array($value)) {
            if (count($value) > 1) {
                switch ($value[0]) {
                    case '!=':
                    case '<=':
                    case '<':
                    case '>=':
                    case '>':
                    case 'INSTANCE OF':
                    case 'NOT IN':
                        $finalQuery->setParameter($key, $value[1]);
                        break;
                    case 'BETWEEN':
                        $finalQuery->setParameter($key . '_1', $value[1]);
                        $finalQuery->setParameter($key . '_2', $value[2]);
                        break;
                    case 'LIKE':
                        // param is setted in filterBy
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
        } elseif ('NOT NULL' == $value) {
            // param is not needed
        } elseif (isset($value)) {
            $finalQuery->setParameter($key, $value);
        }
    }

    /**
     * Count entities using a Criteria object or a simple filter array.
     *
     * @param Criteria|mixed|array $criteria or array
     *
     * @return integer
     */
    public function countBy($criteria)
    {
        if ($criteria instanceof Criteria) {
            $collection = $this->matching($criteria);
            return $collection->count();
        } elseif (is_array($criteria)) {
            $qb = $this->createQueryBuilder(static::DEFAULT_ALIAS);
            $qb->select($qb->expr()->countDistinct(static::DEFAULT_ALIAS . '.id'));
            $qb = $this->prepareComparisons($criteria, $qb, static::DEFAULT_ALIAS);
            $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
            $this->applyFilterByCriteria($criteria, $qb);

            try {
                return (int) $qb->getQuery()->getSingleScalarResult();
            } catch (NoResultException $e) {
                return 0;
            }
        }
        return 0;
    }

    /**
     * Create a LIKE comparison with entity texts colunms.
     *
     * @param string $pattern
     * @param QueryBuilder $qb
     * @param string $alias
     */
    protected function classicLikeComparison(
        $pattern,
        QueryBuilder $qb,
        $alias = EntityRepository::DEFAULT_ALIAS
    ) {
        /*
         * Get fields needed for a search query
         */
        $metadatas = $this->_em->getClassMetadata($this->getEntityName());
        $criteriaFields = [];
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $this->searchableTypes) &&
                $field != 'folder' &&
                $field != 'childrenOrder' &&
                $field != 'childrenOrderDirection') {
                $criteriaFields[$field] = '%' . strip_tags(strtolower($pattern)) . '%';
            }
        }

        foreach ($criteriaFields as $key => $value) {
            $fullKey = sprintf('LOWER(%s)', $alias . '.' . $key);
            $qb->orWhere($qb->expr()->like($fullKey, $qb->expr()->literal($value)));
        }
    }

    /**
     * Create a Criteria object from a search pattern and additional fields.
     *
     * @param string $pattern Search pattern
     * @param QueryBuilder $qb QueryBuilder to pass
     * @param array $criteria Additional criteria
     * @param string $alias SQL query table alias
     * @return QueryBuilder
     */
    protected function createSearchBy(
        $pattern,
        QueryBuilder $qb,
        array &$criteria = [],
        $alias = EntityRepository::DEFAULT_ALIAS
    ) {
        $this->classicLikeComparison($pattern, $qb, $alias);
        $this->prepareComparisons($criteria, $qb, $alias);

        return $qb;
    }

    /**
     * @param string  $pattern  Search pattern
     * @param array   $criteria Additional criteria
     * @param array   $orders
     * @param integer $limit
     * @param integer $offset
     * @param string $alias
     *
     * @return array|Paginator
     */
    public function searchBy(
        $pattern,
        array $criteria = [],
        array $orders = [],
        $limit = null,
        $offset = null,
        $alias = EntityRepository::DEFAULT_ALIAS
    ) {
        $qb = $this->createQueryBuilder($alias);
        $qb = $this->createSearchBy($pattern, $qb, $criteria, $alias);

        // Add ordering
        foreach ($orders as $key => $value) {
            if (strpos($key, static::NODE_ALIAS . '.') !== false &&
                $this->hasJoinedNode($qb, $alias)) {
                $qb->addOrderBy($key, $value);
            } elseif (strpos($key, static::NODESSOURCES_ALIAS . '.') !== false &&
                $this->hasJoinedNodesSources($qb, $alias)) {
                $qb->addOrderBy($key, $value);
            } else {
                $qb->addOrderBy($alias . '.' . $key, $value);
            }
        }
        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByCriteria($criteria, $qb);

        if (null !== $limit &&
            null !== $offset) {
            /*
             * We need to use Doctrine paginator
             * if a limit is set because of the default inner join
             */
            return new Paginator($qb);
        } else {
            return $qb->getQuery()->getResult();
        }
    }

    /**
     * @param string $pattern Search pattern
     * @param array $criteria Additional criteria
     * @return int
     */
    public function countSearchBy($pattern, array $criteria = [])
    {
        $qb = $this->createQueryBuilder(static::DEFAULT_ALIAS);
        $qb->select($qb->expr()->countDistinct(static::DEFAULT_ALIAS . '.id'));
        $qb = $this->createSearchBy($pattern, $qb, $criteria);

        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByCriteria($criteria, $qb);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param  array &$criteria
     * @param  QueryBuilder $qb
     * @param  string $nodeAlias
     */
    protected function buildTagFiltering(array &$criteria, QueryBuilder $qb, $nodeAlias = 'n')
    {
        if (in_array('tags', array_keys($criteria))) {
            /*
             * Do not filter if tag is null
             */
            if (is_null($criteria['tags'])) {
                return;
            }

            if (is_array($criteria['tags']) || $criteria['tags'] instanceof Collection) {
                /*
                 * Do not filter if tag array is empty.
                 */
                if (count($criteria['tags']) === 0) {
                    return;
                }
                if (in_array("tagExclusive", array_keys($criteria))
                    && $criteria["tagExclusive"] === true) {
                    // To get an exclusive tag filter
                    // we need to filter against each tag id
                    // and to inner join with a different alias for each tag
                    // with AND operator
                    foreach ($criteria['tags'] as $index => $tag) {
                        $alias = static::TAG_ALIAS . $index;
                        $qb->innerJoin($nodeAlias . '.tags', $alias);
                        $qb->andWhere($qb->expr()->eq($alias . '.id', $tag->getId()));
                    }
                    unset($criteria["tagExclusive"]);
                    unset($criteria['tags']);
                } else {
                    $qb->innerJoin(
                        $nodeAlias . '.tags',
                        static::TAG_ALIAS,
                        'WITH',
                        'tg.id IN (:tags)'
                    );
                }
            } else {
                $qb->innerJoin(
                    $nodeAlias . '.tags',
                    static::TAG_ALIAS,
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
     * @param QueryBuilder $qb
     */
    protected function applyFilterByTag(array &$criteria, QueryBuilder $qb)
    {
        if (in_array('tags', array_keys($criteria))) {
            if ($criteria['tags'] instanceof Tag) {
                $qb->setParameter('tags', $criteria['tags']->getId());
            } elseif (is_array($criteria['tags']) || $criteria['tags'] instanceof Collection) {
                if (count($criteria['tags']) > 0) {
                    $qb->setParameter('tags', $criteria['tags']);
                }
            } elseif (is_integer($criteria['tags'])) {
                $qb->setParameter('tags', (int) $criteria['tags']);
            }
            unset($criteria['tags']);
        }
    }

    /**
     * Ensure that node table is joined only once.
     *
     * @param  QueryBuilder $qb
     * @param  string  $alias
     * @return boolean
     */
    protected function hasJoinedNode(QueryBuilder $qb, $alias)
    {
        return $this->joinExists($qb, $alias, static::NODE_ALIAS);
    }

    /**
     * Ensure that nodes_sources table is joined only once.
     *
     * @param  QueryBuilder $qb
     * @param  string  $alias
     * @return boolean
     */
    protected function hasJoinedNodesSources(QueryBuilder $qb, $alias)
    {
        return $this->joinExists($qb, $alias, static::NODESSOURCES_ALIAS);
    }

    /**
     * Ensure that nodes_sources table is joined only once.
     *
     * @param  QueryBuilder $qb
     * @param  string  $alias
     * @return boolean
     */
    protected function hasJoinedNodeType(QueryBuilder $qb, $alias)
    {
        return $this->joinExists($qb, $alias, static::NODETYPE_ALIAS);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $rootAlias
     * @param string $joinAlias
     * @return bool
     */
    protected function joinExists(QueryBuilder $qb, $rootAlias, $joinAlias)
    {
        if (isset($qb->getDQLPart('join')[$rootAlias])) {
            foreach ($qb->getDQLPart('join')[$rootAlias] as $join) {
                if (null !== $join &&
                    $join instanceof Join &&
                    $join->getAlias() === $joinAlias) {
                    return true;
                }
            }
        }

        return false;
    }
}
