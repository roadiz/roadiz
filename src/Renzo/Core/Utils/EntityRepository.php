<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file EntityRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

use Doctrine\Common\Collections\Criteria;

/**
 * EntityRepository that implements a simple countBy method.
 */
class EntityRepository extends \Doctrine\ORM\EntityRepository
{
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
            $expr = Criteria::expr();
            $criteriaObj = Criteria::create();
            $i = 0;

            foreach ($criteria as $key => $value) {

                if (is_array($value)) {
                    $res = $expr->in($key, $value);
                } else {
                    $res = $expr->eq($key, $value);
                }


                if ($i == 0) {
                    $criteriaObj->where($res);
                } else {
                    $criteriaObj->andWhere($res);
                }

                $i++;
            }
            $collection = $this->matching($criteriaObj);

            return $collection->count();
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
        array $criteria = array(),
        $alias = "obj"
    ) {
        /*
         * get fields needed for a search
         * query
         */
        $types = array('string', 'text');
        $criteriaFields = array();
        $cols = $this->_em->getClassMetadata($this->getEntityName())->getColumnNames();
        foreach ($cols as $col) {
            $field = $this->_em->getClassMetadata($this->getEntityName())->getFieldName($col);
            $type = $this->_em->getClassMetadata($this->getEntityName())->getTypeOfField($field);

            if (in_array($type, $types)) {
                $criteriaFields[$this->_em->getClassMetadata($this->getEntityName())->getFieldName($col)] =
                    '%'.strip_tags($pattern).'%';
            }
        }

        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like($alias . '.' .$key, $qb->expr()->literal($value)));
        }

        foreach ($criteria as $key => $value) {

            if (is_array($value)) {
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
     * @param array   $orders   [description]
     * @param integer $limit    [description]
     * @param integer $offset   [description]
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function searchBy(
        $pattern,
        array $criteria = array(),
        array $orders = array(),
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

        try {
            return $qb->getQuery()->getResult();
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
    public function countSearchBy($pattern, array $criteria = array())
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'count(obj)')
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
