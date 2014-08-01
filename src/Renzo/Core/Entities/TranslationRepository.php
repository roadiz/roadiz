<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

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
        $query = Kernel::getInstance()->em()
                        ->createQuery('
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
     * 
     * @param  string $locale
     * @return boolean
     */
    public function exists( $locale )
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT COUNT(t.locale) FROM RZ\Renzo\Core\Entities\Translation t 
            WHERE t.locale = :locale
        ')->setParameter('locale', $locale);

        try {
            return (boolean)$query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}