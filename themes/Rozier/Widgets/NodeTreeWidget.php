<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * Prepare a Node tree according to Node hierarchy and given options.
 */
final class NodeTreeWidget extends AbstractWidget
{
    const SESSION_ITEM_PER_PAGE = 'nodetree_item_per_page';

    /**
     * @var Node|null
     */
    protected $parentNode = null;
    /**
     * @var array|null
     */
    protected $nodes = null;
    /**
     * @var Tag|null
     */
    protected $tag = null;
    /**
     * @var Translation|null
     */
    protected $translation = null;
    /**
     * @var array|null
     */
    protected $availableTranslations = null;
    /**
     * @var bool
     */
    protected $stackTree = false;
    /**
     * @var array|null
     */
    protected $filters = null;
    /**
     * @var bool
     */
    protected $canReorder = true;
    /**
     * @var array
     */
    protected $additionalCriteria = [];

    /**
     * @param Request $request Current kernel request
     * @param EntityManagerInterface $entityManager
     * @param Node|null $parent Entry point of NodeTreeWidget, set null if it's root
     * @param Translation|null $translation NodeTree translation
     */
    public function __construct(
        Request $request,
        EntityManagerInterface $entityManager,
        Node $parent = null,
        Translation $translation = null
    ) {
        parent::__construct($request, $entityManager);

        $this->parentNode = $parent;
        $this->translation = $translation;

        if ($this->translation === null) {
            $this->translation = $this->entityManager
                ->getRepository(Translation::class)
                ->findOneBy(['defaultTranslation' => true]);
        }

        $this->availableTranslations = $this->entityManager
             ->getRepository(Translation::class)
             ->findBy([], [
                 'defaultTranslation' => 'DESC',
                 'locale' => 'ASC',
             ]);
    }

    /**
     * @return Tag|null
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param Tag|null $tag
     *
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isStackTree()
    {
        return $this->stackTree;
    }

    /**
     * @param boolean $newstackTree
     *
     * @return $this
     */
    public function setStackTree($newstackTree)
    {
        $this->stackTree = (boolean) $newstackTree;

        return $this;
    }

    /**
     * Fill twig assignation array with NodeTree entities.
     */
    protected function getRootListManager()
    {
        /*
         * Only use additional criteria for ROOT list-manager
         */
        return $this->getListManager($this->parentNode, false, $this->additionalCriteria);
    }

    /**
     * @return array
     */
    public function getAdditionalCriteria(): array
    {
        return $this->additionalCriteria;
    }

    /**
     * @param array $additionalCriteria
     *
     * @return NodeTreeWidget
     */
    public function setAdditionalCriteria(array $additionalCriteria): NodeTreeWidget
    {
        $this->additionalCriteria = $additionalCriteria;
        return $this;
    }

    /**
     * @param Node|null $parent
     * @param bool $subRequest
     *
     * @return bool
     */
    protected function canOrderByParent(Node $parent = null, $subRequest = false)
    {
        if (true === $subRequest || null === $parent) {
            return false;
        }

        if ($parent->getChildrenOrder() !== 'position' &&
            in_array($parent->getChildrenOrder(), Node::$orderingFields) &&
            in_array($parent->getChildrenOrderDirection(), ['ASC', 'DESC'])) {
            return true;
        }

        return false;
    }

    /**
     * @param Node|null $parent
     * @param bool $subRequest Default: false
     * @param array $additionalCriteria Default: []
     * @return EntityListManager
     */
    protected function getListManager(Node $parent = null, $subRequest = false, array $additionalCriteria = [])
    {
        $criteria = array_merge($additionalCriteria, [
            'parent' => $parent,
            'translation' => $this->translation,
        ]);

        if (null !== $this->tag) {
            $criteria['tags'] = $this->tag;
        }

        $ordering = [
            'position' => 'ASC',
        ];

        if (null !== $parent && $this->canOrderByParent($parent, $subRequest)) {
            $ordering = [
                $parent->getChildrenOrder() => $parent->getChildrenOrderDirection(),
            ];
            $this->canReorder = false;
        }
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $this->request,
            $this->entityManager,
            Node::class,
            $criteria,
            $ordering
        );
        $listManager->setDisplayingNotPublishedNodes(true);

        if (true === $this->stackTree) {
            $listManager->setItemPerPage(20);
            $listManager->handle();

            /*
             * Stored in session
             */
            $sessionListFilter = new SessionListFilters(static::SESSION_ITEM_PER_PAGE);
            $sessionListFilter->handleItemPerPage($this->request, $listManager);
        } else {
            $listManager->setItemPerPage(99999);
            $listManager->handle(true);
        }


        if ($subRequest) {
            $listManager->disablePagination();
        }

        return $listManager;
    }

    /**
     * @param Node|null $parent
     * @param bool $subRequest Default: false
     * @return array|Paginator
     */
    public function getChildrenNodes(Node $parent = null, $subRequest = false)
    {
        return $this->getListManager($parent, $subRequest)->getEntities();
    }

    /**
     * @param Node|null $parent
     * @param bool $subRequest Default: false
     * @return array|Paginator
     */
    public function getReachableChildrenNodes(Node $parent = null, $subRequest = false)
    {
        return $this->getListManager($parent, $subRequest, [
            'nodeType.reachable' => true,
        ])->getEntities();
    }

    /**
     * @return Node|null
     */
    public function getRootNode()
    {
        return $this->parentNode;
    }

    /**
     * Get entity list manager filters.
     *
     * Call getNodes() first to populate this.
     *
     * @return array|null
     */
    public function getFilters()
    {
        return $this->filters;
    }
    /**
     * @return Translation|null
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @return array
     */
    public function getAvailableTranslations()
    {
        return $this->availableTranslations ?? [];
    }

    /**
     * @return array|Paginator
     */
    public function getNodes()
    {
        if (null === $this->nodes) {
            $manager = $this->getRootListManager();
            $this->nodes = $manager->getEntities();
            $this->filters = $manager->getAssignation();
        }

        return $this->nodes;
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
