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
	 * @param  Translation $translation
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
	 * @param Translation $translation
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
	 * @return Tag or null
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
     * @param  Tag        $parent      [description]
     * @param  Translation $translation [description]
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
     * @param  Tag        $parent      [description]
     * @param  Translation $translation [description]
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
}