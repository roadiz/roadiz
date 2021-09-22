<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;

/**
 * Class PrefixAwareRepository for defining join-queries prefixes.
 *
 * @template TEntityClass of object
 * @extends \RZ\Roadiz\Core\Repositories\EntityRepository<TEntityClass>
 */
class PrefixAwareRepository extends EntityRepository
{
    /**
     * @var array
     *
     * array [
     *    'nodeType' => [
     *       'type': 'inner',
     *       'joins': [
     *           'n': 'obj.node',
     *           't': 'node.nodeType'
     *        ]
     *    ]
     * ]
     */
    private $prefixes = [];

    /**
     * @return array
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     * @return string
     */
    public function getDefaultPrefix()
    {
        return EntityRepository::DEFAULT_ALIAS;
    }

    /**
     * @param string $prefix Ex. 'node'
     * @param array $joins Ex. ['n': 'obj.node']
     * @param string $type Ex. 'inner'|'left', default 'left'
     * @return $this
     */
    public function addPrefix($prefix, array $joins, $type = 'left')
    {
        if (!in_array($type, ['left', 'inner'])) {
            throw new \InvalidArgumentException('Prefix type can only be "left" or "inner"');
        }

        if (!array_key_exists($prefix, $this->prefixes)) {
            $this->prefixes[$prefix] = [
                'joins' => $joins,
                'type' => $type
            ];
        }

        return $this;
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
                $baseKey = $simpleQB->getParameterKey($key);
                $realKey = $this->getRealKey($qb, $key);
                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($value, $realKey['prefix'], $realKey['key'], $baseKey));
            }
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $key
     * @return array
     */
    protected function getRealKey(QueryBuilder $qb, $key)
    {
        $keyParts = explode('.', $key);
        if (count($keyParts) > 1) {
            if (array_key_exists($keyParts[0], $this->prefixes)) {
                $lastPrefix = '';
                foreach ($this->prefixes[$keyParts[0]]['joins'] as $prefix => $field) {
                    if (!$this->hasJoinedPrefix($qb, $prefix)) {
                        switch ($this->prefixes[$keyParts[0]]['type']) {
                            case 'inner':
                                $qb->innerJoin($field, $prefix);
                                break;
                            case 'left':
                                $qb->leftJoin($field, $prefix);
                                break;
                        }
                    }

                    $lastPrefix = $prefix;
                }
                return [
                    'prefix' => $lastPrefix . '.',
                    'key' => $keyParts[1]
                ];
            }

            throw new \InvalidArgumentException('"' . $keyParts[0] . '" prefix is not known for initiating joined queries.');
        }

        return [
            'prefix' => $this->getDefaultPrefix() . '.',
            'key' => $key
        ];
    }

    /**
     * @param QueryBuilder $qb
     * @param string $prefix
     * @return bool
     */
    protected function hasJoinedPrefix(QueryBuilder $qb, $prefix)
    {
        return $this->joinExists($qb, $this->getDefaultPrefix(), $prefix);
    }

    /**
     * Count entities using a Criteria object or a simple filter array.
     *
     * @param array $criteria
     * @param array|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @return array|Paginator
     * @psalm-return array<TEntityClass>|Paginator<TEntityClass>
     */
    public function findBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        $qb = $this->createQueryBuilder($this->getDefaultPrefix());
        $qb->select($this->getDefaultPrefix());
        $qb = $this->prepareComparisons($criteria, $qb, $this->getDefaultPrefix());

        // Add ordering
        if (null !== $order) {
            foreach ($order as $key => $value) {
                $realKey = $this->getRealKey($qb, $key);
                $qb->addOrderBy($realKey['prefix'] . $realKey['key'], $value);
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
     * Count entities using a Criteria object or a simple filter array.
     *
     * @param array      $criteria
     * @param array|null $order
     *
     * @return Entity
     * @psalm-return TEntityClass
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneBy(
        array $criteria,
        array $order = null
    ) {
        $qb = $this->createQueryBuilder($this->getDefaultPrefix());
        $qb->select($this->getDefaultPrefix());
        $qb = $this->prepareComparisons($criteria, $qb, $this->getDefaultPrefix());

        // Add ordering
        if (null !== $order) {
            foreach ($order as $key => $value) {
                $realKey = $this->getRealKey($qb, $key);
                $qb->addOrderBy($realKey['prefix'] . $realKey['key'], $value);
            }
        }

        $qb->setMaxResults(1);
        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByCriteria($criteria, $qb);
        $query = $qb->getQuery();
        $this->dispatchQueryEvent($query);

        return $query->getOneOrNullResult();
    }

    /**
     * @param string  $pattern Search pattern
     * @param array   $criteria Additional criteria
     * @param array   $orders
     * @param integer $limit
     * @param integer $offset
     * @param string  $alias
     *
     * @return array|Paginator
     * @psalm-return array<TEntityClass>|Paginator<TEntityClass>
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
        $qb->select($alias);
        $qb = $this->createSearchBy($pattern, $qb, $criteria, $alias);

        // Add ordering
        if (null !== $orders) {
            foreach ($orders as $key => $value) {
                $realKey = $this->getRealKey($qb, $key);
                $qb->addOrderBy($realKey['prefix'] . $realKey['key'], $value);
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
     * @param string $pattern Search pattern
     * @param array $criteria Additionnal criteria
     * @return int
     */
    public function countSearchBy($pattern, array $criteria = [])
    {
        $qb = $this->createQueryBuilder($this->getDefaultPrefix());
        $qb->select($qb->expr()->countDistinct($this->getDefaultPrefix().'.id'));
        $qb = $this->createSearchBy($pattern, $qb, $criteria);

        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByCriteria($criteria, $qb);

        return (int) $qb->getQuery()->getSingleScalarResult();
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
        $alias = "obj"
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
                $criteriaFields[$field] = '%' . strip_tags((string) $pattern) . '%';
            }
        }

        foreach ($criteriaFields as $key => $value) {
            $realKey = $this->getRealKey($qb, $key);
            $fullKey = sprintf('LOWER(%s)', $realKey['prefix'] . $realKey['key']);
            $qb->orWhere($qb->expr()->like($fullKey, $qb->expr()->literal($value)));
        }
    }
}
