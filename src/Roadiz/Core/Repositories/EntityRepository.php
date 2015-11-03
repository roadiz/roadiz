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

use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;

/**
 * EntityRepository that implements a simple countBy method.
 */
class EntityRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Doctrine column types that can be search
     * with LIKE feature.
     *
     * @var array
     */
    protected $searchableTypes = ['string', 'text'];

    /**
     * Build a query comparison.
     *
     * @param mixed $value
     * @param string $prefix
     * @param string $key
     * @param string $baseKey
     * @param QueryBuilder $qb
     *
     * @return string
     */
    protected function buildComparison($value, $prefix, $key, $baseKey, &$qb)
    {
        if (is_object($value) && $value instanceof PersistableInterface) {
            $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
        } elseif (is_array($value)) {
            /*
             * array
             *
             * ['!=', $value]
             * ['<=', $value]
             * ['<', $value]
             * ['>=', $value]
             * ['>', $value]
             * ['BETWEEN', $value, $value]
             * ['LIKE', $value]
             * ['NOT IN', [$value]]
             * [$value, $value] (IN)
             */
            if (count($value) > 1) {
                switch ($value[0]) {
                    case '!=':
                        # neq
                        $res = $qb->expr()->neq($prefix.$key, ':'.$baseKey);
                        break;
                    case '<=':
                        # lte
                        $res = $qb->expr()->lte($prefix.$key, ':'.$baseKey);
                        break;
                    case '<':
                        # lt
                        $res = $qb->expr()->lt($prefix.$key, ':'.$baseKey);
                        break;
                    case '>=':
                        # gte
                        $res = $qb->expr()->gte($prefix.$key, ':'.$baseKey);
                        break;
                    case '>':
                        # gt
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
                        $res = $qb->expr()->like($prefix.$key, $qb->expr()->literal($value[1]));
                        break;
                    case 'NOT IN':
                        $res = $qb->expr()->notIn($prefix.$key, ':'.$baseKey);
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
        } elseif ('NOT NULL' == $value) {
            $res = $qb->expr()->isNotNull($prefix.$key);
        } elseif (isset($value)) {
            $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
        } elseif (null === $value) {
            $res = $qb->expr()->isNull($prefix.$key);
        }

        return $res;
    }

    /**
     * Direct bind parameters without preparation.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     * @param string       $alias
     *
     * @return QueryBuilder
     */
    protected function directComparison(&$criteria, &$qb, $alias)
    {
        foreach ($criteria as $key => $value) {
            $qb = $this->singleDirectComparison($key, $value, $qb, $alias);
        }

        return $qb;
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
    protected function singleDirectComparison($key, &$value, &$qb, $alias)
    {
        if (is_object($value) && $value instanceof PersistableInterface) {
            $res = $qb->expr()->eq($alias . '.' .$key, $value->getId());
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
                        $res = $qb->expr()->neq($alias . '.' .$key, $value[1]);
                        break;
                    case '<=':
                        # lte
                        $res = $qb->expr()->lte($alias . '.' .$key, $value[1]);
                        break;
                    case '<':
                        # lt
                        $res = $qb->expr()->lt($alias . '.' .$key, $value[1]);
                        break;
                    case '>=':
                        # gte
                        $res = $qb->expr()->gte($alias . '.' .$key, $value[1]);
                        break;
                    case '>':
                        # gt
                        $res = $qb->expr()->gt($alias . '.' .$key, $value[1]);
                        break;
                    case 'BETWEEN':
                        $res = $qb->expr()->between(
                            $alias . '.' .$key,
                            $value[1],
                            $value[2]
                        );
                        break;
                    case 'LIKE':
                        $res = $qb->expr()->like($alias . '.' .$key, $qb->expr()->literal($value[1]));
                        break;
                    default:
                        $res = $qb->expr()->in($alias . '.' .$key, $value);
                        break;
                }
            } else {
                $res = $qb->expr()->in($alias . '.' .$key, $value);
            }

        } elseif (is_array($value)) {
            $res = $qb->expr()->in($alias . '.' .$key, $value);
        } elseif (is_bool($value)) {
            $res = $qb->expr()->eq($alias . '.' .$key, (boolean) $value);
        } else {
            $res = $qb->expr()->eq($alias . '.' .$key, $value);
        }

        $qb->andWhere($res);

        return $qb;
    }

    /**
     * Bind classic parameters to your query.
     *
     * @param string $key
     * @param mixed  $value
     * @param mixed  $finalQuery
     */
    protected function applyComparison($key, $value, &$finalQuery)
    {
        $key = str_replace('.', '_', $key);

        if (is_object($value) && $value instanceof PersistableInterface) {
            $finalQuery->setParameter($key, $value->getId());
        } elseif (is_array($value)) {
            if (count($value) > 1) {
                switch ($value[0]) {
                    case '!=':
                    case '<=':
                    case '<':
                    case '>=':
                    case '>':
                    case 'NOT IN':
                        $finalQuery->setParameter($key, $value[1]);
                        break;
                    case 'BETWEEN':
                        $finalQuery->setParameter($key.'_1', $value[1]);
                        $finalQuery->setParameter($key.'_2', $value[2]);
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
        } elseif (null === $value) {
            // param is not needed
        }
    }

    /**
     * Count entities using a Criteria object or a simple filter array.
     *
     * @param mixed $criteria Doctrine\Common\Collections\Criteria or array
     *
     * @return integer
     */
    public function countBy($criteria)
    {
        if ($criteria instanceof Criteria) {
            $collection = $this->matching($criteria);

            return $collection->count();
        } elseif (is_array($criteria)) {
            $qb = $this->_em->createQueryBuilder();
            $qb->add('select', 'count(obj.id)')
               ->add('from', $this->getEntityName() . ' obj');

            foreach ($criteria as $key => $value) {
                $baseKey = str_replace('.', '_', $key);
                $qb->andWhere($this->buildComparison($value, 'obj.', $key, $baseKey, $qb));
            }

            $finalQuery = $qb->getQuery();

            /*
             * Reimplementing findBy features…
             */
            foreach ($criteria as $key => $value) {
                $this->applyComparison($key, $value, $finalQuery);
            }

            try {
                return $finalQuery->getSingleScalarResult();
            } catch (\Doctrine\ORM\Query\QueryException $e) {
                return 0;
            } catch (\Doctrine\ORM\NoResultException $e) {
                return 0;
            }
        }
    }

    /**
     * Create a LIKE comparison with entity texts colunms.
     *
     * @param string                  $pattern
     * @param DoctrineORMQueryBuilder $qb
     * @param string                  $alias
     */
    protected function classicLikeComparison(
        $pattern,
        \Doctrine\ORM\QueryBuilder &$qb,
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
                $criteriaFields[$field] = '%'.strip_tags($pattern).'%';
            }
        }

        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like($alias . '.' .$key, $qb->expr()->literal($value)));
        }
    }

   /**
    * Create a Criteria object from a search pattern and additionnal fields.
    *
    * @param string                  $pattern  Search pattern
    * @param DoctrineORMQueryBuilder $qb       QueryBuilder to pass
    * @param array                   $criteria Additionnal criteria
    * @param string                  $alias    SQL query table alias
    *
    * @return \Doctrine\ORM\QueryBuilder
    */
    protected function createSearchBy(
        $pattern,
        \Doctrine\ORM\QueryBuilder $qb,
        array $criteria = [],
        $alias = "obj"
    ) {

        $this->classicLikeComparison($pattern, $qb, $alias);

        foreach ($criteria as $key => $value) {
            if (is_object($value) && $value instanceof PersistableInterface) {
                $res = $qb->expr()->eq($alias . '.' .$key, $value->getId());
            } elseif (is_array($value)) {
                $res = $qb->expr()->in($alias . '.' .$key, $value);
            } elseif (is_bool($value)) {
                $res = $qb->expr()->eq($alias . '.' .$key, (int) $value);
            } else {
                $res = $qb->expr()->eq($alias . '.' .$key, $value);
            }

            $qb->andWhere($res);
        }

        return $qb;
    }

    /**
     * @param string  $pattern  Search pattern
     * @param array   $criteria Additionnal criteria
     * @param array   $orders
     * @param integer $limit
     * @param integer $offset
     *
     * @return array
     */
    public function searchBy(
        $pattern,
        array $criteria = [],
        array $orders = [],
        $limit = null,
        $offset = null
    ) {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'obj')
           ->add('from', $this->getEntityName() . ' obj');

        $qb = $this->createSearchBy($pattern, $qb, $criteria, 'obj');

        // Add ordering
        foreach ($orders as $key => $value) {
            $qb->addOrderBy('obj.'.$key, $value);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $finalQuery = $qb->getQuery();

        try {
            return $finalQuery->getResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return null;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $pattern  Search pattern
     * @param array  $criteria Additionnal criteria
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function countSearchBy($pattern, array $criteria = [])
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'count(distinct obj.id)')
           ->add('from', $this->getEntityName() . ' obj');

        $qb = $this->createSearchBy($pattern, $qb, $criteria);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return null;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
