<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Kernel;


class TagRepository extends EntityRepository
{
	
	/**
	 * 
	 * @param  integer  $tag_id 
	 * @param  Translation $translation
	 * @return Tag or null
	 */
	public function findWithTranslation($tag_id, Translation $translation )
	{
	    $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id AND tr.id = :translation_id'
                        )->setParameter('tag_id', (int)$tag_id)
                        ->setParameter('translation_id', (int)$translation->getId());

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

	/**
	 * 
	 * @param Translation $translation
	 * @return array
	 */
	public function findAllWithTranslation( Translation $translation )
	{
	    $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE tr.id = :translation_id'
                        )->setParameter('translation_id', (int)$translation->getId());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

	/**
	 * 
	 * @param  integer  $tag_id  
	 * @return Tag or null
	 */
	public function findWithDefaultTranslation($tag_id)
	{
	    $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id AND tr.defaultTranslation = :defaultTranslation'
                        )->setParameter('tag_id', (int)$tag_id)
                        ->setParameter('defaultTranslation', true);

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
	    $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE tr.defaultTranslation = :defaultTranslation'
                        )->setParameter('defaultTranslation', true);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

    /**
     * 
     * @param  Tag        $parent      [description]
     * @param  Translation $translation [description]
     * @return array Doctrine result array
     */
    public function findByParentWithTranslation( Tag $parent = null, Translation $translation )
    {
        $query = null;

        if ($parent === null) {
            $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.id = :translation_id
            ORDER BY t.position ASC'
                        )->setParameter('translation_id', (int)$translation->getId());
        }
        else {
            $query = Kernel::getInstance()->em()
                            ->createQuery('
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
     * @param  Tag        $parent      [description]
     * @param  Translation $translation [description]
     * @return array Doctrine result array
     */
    public function findByParentWithDefaultTranslation( Tag $parent = null )
    {
        $query = null;
        if ($parent === null) {
            $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
            INNER JOIN t.translatedTags tt 
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.defaultTranslation = :defaultTranslation
            ORDER BY t.position ASC'
                        )->setParameter('defaultTranslation', true);
        }
        else {
            $query = Kernel::getInstance()->em()
                            ->createQuery('
                SELECT t, tt FROM RZ\Renzo\Core\Entities\Tag t 
                INNER JOIN t.translatedTags tt 
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.id = :translation_id
                ORDER BY t.position ASC'
                            )->setParameter('parent', $parent->getId())
                            ->setParameter('defaultTranslation', true);
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}