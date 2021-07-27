<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Repositories\TagRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Prepare a Tag tree according to Tag hierarchy and given options.
 */
final class TagTreeWidget extends AbstractWidget
{
    protected ?Tag $parentTag = null;
    /**
     * @var array<Tag>|Paginator<Tag>|null
     */
    protected $tags = null;
    protected bool $canReorder = true;
    protected bool $forceTranslation = false;

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $managerRegistry
     * @param Tag|null $parent
     * @param bool $forceTranslation
     */
    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        Tag $parent = null,
        bool $forceTranslation = false
    ) {
        parent::__construct($requestStack, $managerRegistry);

        $this->parentTag = $parent;
        $this->forceTranslation = $forceTranslation;
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
            $criteria['translation'] = $this->getTranslation();
        }
        $this->tags = $this->getTagRepository()->findBy($criteria, $ordering);
    }

    /**
     * @param Tag|null $parent
     *
     * @return array<Tag>|Paginator<Tag>|null
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
                $criteria['translation'] = $this->getTranslation();
            }

            $this->tags = $this->getTagRepository()->findBy($criteria, $ordering);

            return $this->tags;
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
     * @return array<Tag>|Paginator<Tag>|null
     */
    public function getTags()
    {
        if ($this->tags === null) {
            $this->getTagTreeAssignationForParent();
        }
        return $this->tags;
    }

    /**
     * @return TagRepository
     */
    protected function getTagRepository()
    {
        return $this->getManagerRegistry()->getRepository(Tag::class);
    }

    /**
     * Gets the value of canReorder.
     *
     * @return bool
     */
    public function getCanReorder()
    {
        return $this->canReorder;
    }
}
