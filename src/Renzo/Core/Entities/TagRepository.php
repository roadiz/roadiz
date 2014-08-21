<?php 

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;

use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Kernel;


class TagRepository extends EntityRepository
{
	
	/**
	 * 
	 * @param  integer  $tag_id 
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return Tag or null
	 */
	public function findWithTranslation($tag_id, Translation $translation )
	{
	    $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            WHERE t.id = :tag_id 
            AND tt.translation = :translation'
                        )->setParameter('tag_id', (int)$tag_id)
                        ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

	/**
	 * 
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return array
	 */
	public function findAllWithTranslation( Translation $translation )
	{
	    $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            WHERE tt.translation = :translation'
                        )->setParameter('translation', $translation);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

	/**
	 * 
	 * @param  integer  $tag_id  
	 * @return RZ\Renzo\Core\Entities\Tag or null
	 */
	public function findWithDefaultTranslation($tag_id)
	{
	    $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id 
            AND tr.defaultTranslation = 1'
                        )->setParameter('tag_id', (int)$tag_id);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {

            return null;
        }
	}

	/**
	 * @return array
	 */
	public function findAllWithDefaultTranslation(  )
	{
	    $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE tr.defaultTranslation = 1');
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

    /**
     * 
     * @param  RZ\Renzo\Core\Entities\Tag         $parent      [description]
     * @param  RZ\Renzo\Core\Entities\Translation $translation [description]
     * @return array Doctrine result array
     */
    public function findByParentWithTranslation( Tag $parent = null, Translation $translation )
    {
        $query = null;

        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.id = :translation_id
            ORDER BY t.position ASC'
                        )->setParameter('translation_id', (int)$translation->getId());
        }
        else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
                INNER JOIN t.translatedTags tt 
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.id = :translation_id
                ORDER BY t.position ASC'
                            )->setParameter('parent', $parent->getId())
                            ->setParameter('translation_id', (int)$translation->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * 
     * @param  RZ\Renzo\Core\Entities\Tag         $parent      [description]
     * @param  RZ\Renzo\Core\Entities\Translation $translation [description]
     * @return array Doctrine result array
     */
    public function findByParentWithDefaultTranslation( Tag $parent = null )
    {
        $query = null;
        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.defaultTranslation = 1
            ORDER BY t.position ASC'
                        );
        }
        else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
                INNER JOIN t.translatedTags tt 
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.defaultTranslation = 1
                ORDER BY t.position ASC'
                            )->setParameter('parent', $parent->getId());
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
     * @param  string $pattern  Search pattern
     * @param  array  $criteria Additionnal criteria
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createSearchBy( $pattern, array $criteria = array(), \Doctrine\ORM\QueryBuilder $qb, $alias = "obj"  )
    {
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
            }
            elseif (is_bool($value)) {
                $res = $qb->expr()->eq('t.' .$key, (int)$value);
            }
            else {
                $res = $qb->expr()->eq('t.' .$key, $value);
            }

            $qb->andWhere($res);
        }

        return $qb;
    }

    /**
     * 
     * @param  string $pattern  Search pattern
     * @param  array  $criteria Additionnal criteria
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function searchBy( $pattern, array $criteria = array(), array $orders = array(), $limit = null, $offset = null )
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 't, obj')
           ->add('from',  $this->getEntityName() . ' t')
           ->innerJoin('t.translatedTags', 'obj');

        $qb = $this->createSearchBy($pattern, $criteria, $qb, 'obj');
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
        }
        catch(\Doctrine\ORM\Query\QueryException $e){
            return null;
        }
        catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * 
     * @param  string $pattern  Search pattern
     * @param  array  $criteria Additionnal criteria
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function countSearchBy( $pattern, array $criteria = array() )
    {
        $qb = $this->_em->createQueryBuilder();
         $qb->add('select', 'count(t)')
           ->add('from',  $this->getEntityName() . ' t')
           ->innerJoin('t.translatedTags', 'obj');

        $qb = $this->createSearchBy($pattern, $criteria, $qb);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        }
        catch(\Doctrine\ORM\Query\QueryException $e){
            return null;
        }
        catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}