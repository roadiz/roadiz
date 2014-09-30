<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file TagRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Repositories;

use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class TagRepository extends EntityRepository
{
    /**
     * @param integer                            $tagId
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return RZ\Renzo\Core\Entities\Tag
     */
    public function findWithTranslation($tagId, Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            WHERE t.id = :tag_id
            AND tt.translation = :translation')
                        ->setParameter('tag_id', (int) $tagId)
                        ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return ArrayCollection
     */
    public function findAllWithTranslation(Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            WHERE tt.translation = :translation')
                        ->setParameter('translation', $translation);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param integer $tagId
     *
     * @return RZ\Renzo\Core\Entities\Tag
     */
    public function findWithDefaultTranslation($tagId)
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id
            AND tr.defaultTranslation = true')
                        ->setParameter('tag_id', (int) $tagId);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @return ArrayCollection
     */
    public function findAllWithDefaultTranslation()
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE tr.defaultTranslation = true');
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     * @param RZ\Renzo\Core\Entities\Tag         $parent
     *
     * @return array Doctrine result array
     */
    public function findByParentWithTranslation(Translation $translation, Tag $parent = null)
    {
        $query = null;

        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.id = :translation_id
            ORDER BY t.position ASC')
                            ->setParameter('translation_id', (int) $translation->getId());
        } else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
                INNER JOIN t.translatedTags tt
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.id = :translation_id
                ORDER BY t.position ASC')
                            ->setParameter('parent', $parent->getId())
                            ->setParameter('translation_id', (int) $translation->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag $parent
     *
     * @return ArrayCollection
     */
    public function findByParentWithDefaultTranslation(Tag $parent = null)
    {
        $query = null;
        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.defaultTranslation = true
            ORDER BY t.position ASC');
        } else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t
                INNER JOIN t.translatedTags tt
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.defaultTranslation = true
                ORDER BY t.position ASC')
                            ->setParameter('parent', $parent->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }


    /**
     * Create a Criteria object from a search pattern and additionnal fields.
     *
     * @param string                     $pattern  Search pattern
     * @param \Doctrine\ORM\QueryBuilder $qb       QueryBuilder
     * @param array                      $criteria Additionnal criteria
     * @param string                     $alias    SQL table alias
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
        $realSearchEntity = 'RZ\Renzo\Core\Entities\TagTranslation';
        $types = array('string', 'text');
        $criteriaFields = array();
        $cols = $this->_em->getClassMetadata($realSearchEntity)->getColumnNames();

        foreach ($cols as $col) {
            $field = $this->_em->getClassMetadata($realSearchEntity)->getFieldName($col);
            $type = $this->_em->getClassMetadata($realSearchEntity)->getTypeOfField($field);

            if (in_array($type, $types)) {
                $criteriaFields[$this->_em->getClassMetadata($realSearchEntity)->getFieldName($col)] =
                    '%'.strip_tags($pattern).'%';
            }
        }

        /*
         * Search criteria operate on TagTranslation
         */
        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like($alias . '.' .$key, $qb->expr()->literal($value)));
        }

        /*
         * Standard criteria operate on Tag -> t
         */
        foreach ($criteria as $key => $value) {

            if (is_array($value)) {
                $res = $qb->expr()->in('t.' .$key, $value);
            } elseif (is_bool($value)) {
                $res = $qb->expr()->eq('t.' .$key, (int) $value);
            } else {
                $res = $qb->expr()->eq('t.' .$key, $value);
            }

            $qb->andWhere($res);
        }

        return $qb;
    }

    /**
     * @param string  $pattern  Search pattern
     * @param array   $criteria Additionnal criteria
     * @param array   $orders   Ordering criteria
     * @param integer $limit    SQL query limit
     * @param integer $offset   SQL query offset
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
        $qb->add('select', 't, obj')
           ->add('from', $this->getEntityName() . ' t')
           ->innerJoin('t.translatedTags', 'obj');

        $qb = $this->createSearchBy($pattern, $qb, $criteria, 'obj');
        foreach ($orders as $key => $value) {
            $qb->addOrderBy('obj.'.$key, $value);
        }

        if ($offset > -1) {
            $qb->setFirstResult($offset);
        }
        if ($limit !== null) {
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
        $qb->add('select', 'count(t)')
           ->add('from', $this->getEntityName() . ' t')
           ->innerJoin('t.translatedTags', 'obj');

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
