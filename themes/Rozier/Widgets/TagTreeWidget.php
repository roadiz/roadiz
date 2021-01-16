<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Tag tree according to Tag hierarchy and given options.
 */
final class TagTreeWidget extends AbstractWidget
{
    protected $parentTag = null;
    protected $tags = null;
    protected $translation = null;
    protected $canReorder = true;
    protected $forceTranslation = false;

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param Tag|null $parent
     * @param bool $forceTranslation
     */
    public function __construct(
        Request $request,
        EntityManagerInterface $entityManager,
        Tag $parent = null,
        bool $forceTranslation = false
    ) {
        parent::__construct($request, $entityManager);

        $this->parentTag = $parent;
        $this->forceTranslation = $forceTranslation;
        $this->translation = $this->entityManager
            ->getRepository(Translation::class)
            ->findOneBy(['defaultTranslation' => true]);
        $this->getTagTreeAssignationForParent();
    }

    /**
     * Fill twig assignation array with TagTree entities.
     */
    protected function getTagTreeAssignationForParent()
    {
        $ordering = [
            'position' => 'ASC',
        ];
        if (null !== $this->parentTag &&
            $this->parentTag->getChildrenOrder() !== 'order' &&
            $this->parentTag->getChildrenOrder() !== 'position') {
            $ordering = [
                $this->parentTag->getChildrenOrder() => $this->parentTag->getChildrenOrderDirection(),
            ];

            $this->canReorder = false;
        }
        $criteria = [
            'parent' => $this->parentTag,
        ];
        if ($this->forceTranslation) {
            $criteria['translation'] = $this->translation;
        }
        $this->tags = $this->entityManager
             ->getRepository(Tag::class)
            ->findBy($criteria, $ordering);
    }

    /**
     * @param Tag|null $parent
     *
     * @return ArrayCollection|null
     */
    public function getChildrenTags(?Tag $parent)
    {
        if ($parent !== null) {
            $ordering = [
                'position' => 'ASC',
            ];
            if ($parent->getChildrenOrder() !== 'order' &&
                $parent->getChildrenOrder() !== 'position') {
                $ordering = [
                    $parent->getChildrenOrder() => $parent->getChildrenOrderDirection(),
                ];
            }

            $criteria = [
                'parent' => $parent,
            ];
            if ($this->forceTranslation) {
                $criteria['translation'] = $this->translation;
            }

            return $this->tags = $this->entityManager
                        ->getRepository(Tag::class)
                        ->findBy($criteria, $ordering);
        }

        return null;
    }
    /**
     * @return Tag
     */
    public function getRootTag()
    {
        return $this->parentTag;
    }
    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Gets the value of canReorder.
     *
     * @return boolean
     */
    public function getCanReorder()
    {
        return $this->canReorder;
    }
}
