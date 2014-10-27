<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file TagHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\TagType;
use RZ\Renzo\Core\Entities\TagTypeField;
use RZ\Renzo\Core\Entities\Translation;

/**
 * Handle operations with tags entities.
 */
class TagHandler
{
    private $tag = null;

    /**
     * @return RZ\Renzo\Core\Entities\Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag $tag
     *
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }
    /**
     * Create a new tag handler with tag to handle.
     *
     * @param Tag $tag
     */
    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * Remove only current tag children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        foreach ($this->tag->getChildren() as $tag) {
            $tag->getHandler()->removeWithChildrenAndAssociations();
        }

        return $this;
    }
    /**
     * Remove only current tag associations.
     *
     * @return $this
     */
    public function removeAssociations()
    {
        foreach ($this->tag->getTranslatedTags() as $tt) {
            Kernel::getService('em')->remove($tt);
        }

        return $this;
    }
    /**
     * Remove current tag with its children recursively and
     * its associations.
     *
     * @return $this
     */
    public function removeWithChildrenAndAssociations()
    {
        $this->removeChildren();
        $this->removeAssociations();

        Kernel::getService('em')->remove($this->tag);

        /*
         * Final flush
         */
        Kernel::getService('em')->flush();

        return $this;
    }

    /**
     * @return ArrayCollection ArrayCollection of Translation
     */
    public function getAvailableTranslations()
    {
        $query = Kernel::getService('em')
                        ->createQuery('
            SELECT tr
            FROM RZ\Renzo\Core\Entities\Translation tr
            INNER JOIN tr.tagTranslations tt
            INNER JOIN tt.tag t
            WHERE t.id = :tag_id')
                        ->setParameter('tag_id', $this->tag->getId());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
    /**
     * @return array Array of Translation id
     */
    public function getAvailableTranslationsId()
    {
        $query = Kernel::getService('em')
                        ->createQuery('
            SELECT tr.id FROM RZ\Renzo\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id')
                        ->setParameter('tag_id', $this->tag->getId());

        try {

            $simpleArray = array();
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return array();
        }
    }

    /**
     * @return ArrayCollection ArrayCollection of Translation
     */
    public function getUnavailableTranslations()
    {
        $query = Kernel::getService('em')
                        ->createQuery('
            SELECT tr FROM RZ\Renzo\Core\Entities\Translation tr
            WHERE tr.id NOT IN (:translations_id)')
                        ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @return array Array of Translation id
     */
    public function getUnavailableTranslationsId()
    {
        $query = Kernel::getService('em')
                        ->createQuery('
            SELECT t.id FROM RZ\Renzo\Core\Entities\Translation t
            WHERE t.id NOT IN (:translations_id)')
                        ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            $simpleArray = array();
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Return every tag’s parents.
     *
     * @return array
     */
    public function getParents()
    {
        $parentsArray = array();
        $parent = $this->tag;

        do {
            $parent = $parent->getParent();
            if ($parent !== null) {
                $parentsArray[] = $parent;
            } else {
                break;
            }
        } while ($parent !== null);

        return array_reverse($parentsArray);
    }

    /**
     * Get tag full path using tag names.
     *
     * @return string
     */
    public function getFullPath()
    {
        $parents = $this->getParents();
        $path = array();

        foreach ($parents as $parent) {
            $path[] = $parent->getTagName();
        }

        $path[] = $this->tag->getTagName();

        return implode('/', $path);
    }

    /**
     * Clean position for current tag siblings.
     *
     * @return int Return the next position after the **last** tag
     */
    public function cleanPositions()
    {
        if ($this->tag->getParent() !== null) {
            return $this->tag->getParent()->getHandler()->cleanChildrenPositions();
        } else {
            return static::cleanRootTagsPositions();
        }
    }

    /**
     * Reset current tag children positions.
     *
     * @return int Return the next position after the **last** tag
     */
    public function cleanChildrenPositions()
    {
        $children = $this->tag->getChildren();
        $i = 1;
        foreach ($children as $child) {
            $child->setPosition($i);
            $i++;
        }

        Kernel::getService('em')->flush();

        return $i;
    }

    /**
     * Reset every root tags positions.
     *
     * @return int Return the next position after the **last** tag
     */
    public static function cleanRootTagsPositions()
    {
        $tags = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Tag')
            ->findBy(array('parent' => null), array('position'=>'ASC'));

        $i = 1;
        foreach ($tags as $child) {
            $child->setPosition($i);
            $i++;
        }

        Kernel::getService('em')->flush();

        return $i;
    }
}
