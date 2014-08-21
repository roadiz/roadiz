<?php 

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class TranslationRepository extends EntityRepository
{	

    /**
     * Get single default translation
     * 
     * @return array
     */
	public function findDefault( )
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Renzo\Core\Entities\Translation t 
            WHERE t.defaultTranslation = 1 
            AND t.available = 1
        ');

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get all available translations
     * 
     * @return array
     */
    public function findAllAvailable( )
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Renzo\Core\Entities\Translation t 
            WHERE t.available = 1
        ');

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * 
     * @param  string $locale
     * @return boolean
     */
    public function exists( $locale )
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(t.locale) FROM RZ\Renzo\Core\Entities\Translation t 
            WHERE t.locale = :locale
        ')->setParameter('locale', $locale);

        try {
            return (boolean)$query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * 
     * @param  string $pattern  Search pattern
     * @param  array  $criteria Additionnal criteria
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function searchBy($pattern, array $criteria = array(), array $orders = array(), $limit = null, $offset = null ) {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 't, obj')
           ->add('from',  $this->getEntityName() . ' t');

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
}