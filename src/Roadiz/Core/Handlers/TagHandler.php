<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use RZ\Roadiz\Core\Entities\Tag;

/**
 * Handle operations with tags entities.
 */
class TagHandler extends AbstractHandler
{
    private ?Tag $tag = null;

    /**
     * @return Tag
     */
    public function getTag(): Tag
    {
        if (null === $this->tag) {
            throw new \BadMethodCallException('Tag is null');
        }
        return $this->tag;
    }

    /**
     * @param Tag $tag
     * @return $this
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * Remove only current tag children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        /** @var Tag $tag */
        foreach ($this->tag->getChildren() as $tag) {
            $handler = new TagHandler($this->objectManager);
            $handler->setTag($tag);
            $handler->removeWithChildrenAndAssociations();
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
            $this->objectManager->remove($tt);
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

        $this->objectManager->remove($this->tag);

        /*
         * Final flush
         */
        $this->objectManager->flush();

        return $this;
    }

    /**
     * @return array Array of Translation
     * @deprecated Do not query DB here
     */
    public function getAvailableTranslations()
    {
        $query = $this->objectManager
                        ->createQuery('
            SELECT tr
            FROM RZ\Roadiz\Core\Entities\Translation tr
            INNER JOIN tr.tagTranslations tt
            INNER JOIN tt.tag t
            WHERE t.id = :tag_id')
                        ->setParameter('tag_id', $this->tag->getId());

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }
    /**
     * @return array Array of Translation id
     * @deprecated Do not query DB here
     */
    public function getAvailableTranslationsId()
    {
        $query = $this->objectManager
                        ->createQuery('
            SELECT tr.id FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id')
                        ->setParameter('tag_id', $this->tag->getId());

        try {
            $simpleArray = [];
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @return array Array of Translation
     * @deprecated Do not query DB here
     */
    public function getUnavailableTranslations()
    {
        $query = $this->objectManager
                        ->createQuery('
            SELECT tr FROM RZ\Roadiz\Core\Entities\Translation tr
            WHERE tr.id NOT IN (:translations_id)')
                        ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @return array Array of Translation id
     * @deprecated Do not query DB here
     */
    public function getUnavailableTranslationsId()
    {
        /** @var Query $query */
        $query = $this->objectManager
                        ->createQuery('
            SELECT t.id FROM RZ\Roadiz\Core\Entities\Translation t
            WHERE t.id NOT IN (:translations_id)')
                        ->setParameter('translations_id', $this->getAvailableTranslationsId());

        try {
            $simpleArray = [];
            $complexArray = $query->getScalarResult();
            foreach ($complexArray as $subArray) {
                $simpleArray[] = $subArray['id'];
            }

            return $simpleArray;
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Return every tag’s parents.
     * @deprecated Use directly Tag::getParents
     * @return array
     */
    public function getParents()
    {
        return $this->tag->getParents();
    }

    /**
     * Get tag full path using tag names.
     *
     * @deprecated Use directly Tag::getFullPath
     * @return string
     */
    public function getFullPath(): string
    {
        return $this->tag->getFullPath();
    }

    /**
     * Clean position for current tag siblings.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** tag
     */
    public function cleanPositions(bool $setPositions = true): float
    {
        if ($this->tag->getParent() !== null) {
            $tagHandler = new TagHandler($this->objectManager);
            $tagHandler->setTag($this->tag->getParent());
            return $tagHandler->cleanChildrenPositions($setPositions);
        } else {
            return $this->cleanRootTagsPositions($setPositions);
        }
    }

    /**
     * Reset current tag children positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** tag
     */
    public function cleanChildrenPositions(bool $setPositions = true): float
    {
        /*
         * Force collection to sort on position
         */
        $sort = Criteria::create();
        $sort->orderBy([
            'position' => Criteria::ASC
        ]);

        $children = $this->tag->getChildren()->matching($sort);
        $i = 1;
        /** @var Tag $child */
        foreach ($children as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }

    /**
     * Reset every root tags positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return float Return the next position after the **last** tag
     */
    public function cleanRootTagsPositions(bool $setPositions = true): float
    {
        $tags = $this->objectManager
            ->getRepository(Tag::class)
            ->findBy(['parent' => null], ['position'=>'ASC']);

        $i = 1;
        /** @var Tag $child */
        foreach ($tags as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }
}
