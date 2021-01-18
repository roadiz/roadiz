<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Repositories\StatusAwareRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Perform basic filtering and search over entity listings.
 *
 * @package RZ\Roadiz\Core\ListManagers
 */
class EntityListManager implements EntityListManagerInterface
{
    const ITEM_PER_PAGE = 20;

    /**
     * @var null|Request
     */
    protected $request = null;
    /**
     * @var EntityManagerInterface|null
     */
    protected $entityManager = null;
    /**
     * @var string
     */
    protected $entityName;
    /**
     * @var Paginator
     */
    protected $paginator = null;
    /**
     * @var bool
     */
    protected $pagination = true;
    /**
     * @var array|null
     */
    protected $orderingArray = null;
    /**
     * @var array|null
     */
    protected $filteringArray = null;
    /**
     * @var array|null
     */
    protected $queryArray = null;
    /**
     * @var string|null
     */
    protected $searchPattern = null;
    /**
     * @var int|null
     */
    protected $currentPage = null;
    /**
     * @var array|null
     */
    protected $assignation = null;
    /**
     * @var int|null
     */
    protected $itemPerPage = null;
    /**
     * @var Translation|null
     */
    protected $translation = null;
    /**
     * @var bool
     */
    protected $displayNotPublishedNodes;
    /**
     * @var bool
     */
    protected $displayAllNodesStatuses;

    /**
     * @param Request|null  $request
     * @param EntityManagerInterface $entityManager
     * @param string        $entityName
     * @param array         $preFilters
     * @param array         $preOrdering
     */
    public function __construct(
        ?Request $request,
        EntityManagerInterface $entityManager,
        string $entityName,
        $preFilters = [],
        $preOrdering = []
    ) {
        $this->request = $request;
        $this->entityName = $entityName;
        $this->entityManager = $entityManager;
        $this->displayNotPublishedNodes = false;
        $this->displayAllNodesStatuses = false;

        $this->orderingArray = $preOrdering;
        $this->filteringArray = $preFilters;
        $this->assignation = [];
        $this->queryArray = array_filter($request->query->all());
        $this->itemPerPage = static::ITEM_PER_PAGE;
    }

    /**
     * @return bool
     */
    public function isDisplayingNotPublishedNodes()
    {
        return $this->displayNotPublishedNodes;
    }

    /**
     * @param bool $displayNotPublishedNodes
     * @return EntityListManager
     */
    public function setDisplayingNotPublishedNodes($displayNotPublishedNodes)
    {
        $this->displayNotPublishedNodes = $displayNotPublishedNodes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisplayingAllNodesStatuses()
    {
        return $this->displayAllNodesStatuses;
    }

    /**
     * Switch repository to disable any security on Node status. To use ONLY in order to
     * view deleted and archived nodes.
     *
     * @param bool $displayAllNodesStatuses
     * @return $this
     */
    public function setDisplayingAllNodesStatuses($displayAllNodesStatuses)
    {
        $this->displayAllNodesStatuses = $displayAllNodesStatuses;
        return $this;
    }

    /**
     * @return $this
     */
    public function enablePagination()
    {
        $this->pagination = true;
        return $this;
    }

    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param Translation|null $translation
     * @return $this
     */
    public function setTranslation(Translation $translation = null)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Handle request to find filter to apply to entity listing.
     *
     * @param boolean $disabled Disable pagination and filtering over GET params
     * @return void
     */
    public function handle($disabled = false)
    {
        // transform the key chroot in parent
        if (array_key_exists('chroot', $this->filteringArray)) {
            if ($this->filteringArray["chroot"] instanceof Node) {
                /** @var NodeRepository $nodeRepo */
                $nodeRepo = $this->entityManager
                    ->getRepository(Node::class)
                    ->setDisplayingNotPublishedNodes($this->isDisplayingNotPublishedNodes())
                    ->setDisplayingAllNodesStatuses($this->isDisplayingAllNodesStatuses());
                $ids = $nodeRepo->findAllOffspringIdByNode($this->filteringArray["chroot"]); // get all offspringId
                if (array_key_exists('parent', $this->filteringArray)) {
                    // test if parent key exist
                    if (is_array($this->filteringArray["parent"])) {
                        // test if multiple parent id
                        if (count(array_intersect($this->filteringArray["parent"], $ids))
                            != count($this->filteringArray["parent"])) {
                            // test if all parent are in the chroot
                            $this->filteringArray["parent"] = -1; // -1 for make the search return []
                        }
                    } else {
                        if ($this->filteringArray["parent"] instanceof Node) {
                            // make transform all id in int
                            $parent = $this->filteringArray["parent"]->getId();
                        } else {
                            $parent = (int) $this->filteringArray["parent"];
                        }
                        if (!in_array($parent, $ids, true)) {
                            $this->filteringArray["parent"] = -1;
                        }
                    }
                } else {
                    $this->filteringArray["parent"] = $ids;
                }
            }
            unset($this->filteringArray["chroot"]); // remove placeholder
        }

        if (false === $disabled && null !== $this->request) {
            if ($this->request->query->get('field') &&
                $this->request->query->get('ordering')) {
                $this->orderingArray = [
                    $this->request->query->get('field') => $this->request->query->get('ordering')
                ];
                $this->queryArray['field'] = $this->request->query->get('field');
                $this->queryArray['ordering'] = $this->request->query->get('ordering');
            }

            if ($this->request->query->get('search') != "") {
                $this->searchPattern = $this->request->query->get('search');
                $this->queryArray['search'] = $this->request->query->get('search');
            }

            if ($this->request->query->has('item_per_page') &&
                $this->request->query->get('item_per_page') > 0) {
                $this->setItemPerPage($this->request->query->get('item_per_page'));
            }

            if ($this->request->query->has('page') &&
                $this->request->query->get('page') > 1) {
                $this->setPage($this->request->query->get('page'));
            } else {
                $this->setPage(1);
            }
        } else {
            /*
             * Disable pagination and paginator
             */
            $this->disablePagination();
        }

        $this->createPaginator();

        if (false === $disabled && null !== $this->request) {
            if ($this->request->query->get('search') != "") {
                $this->paginator->setSearchPattern($this->request->query->get('search'));
            }
        }
    }

    /**
     * Configure a custom current page.
     *
     * @param int $page
     *
     * @return $this
     */
    public function setPage($page)
    {
        if ($page < 1) {
            throw new \RuntimeException("Page cannot be lesser than 1.", 1);
        }
        $this->currentPage = (int) $page;

        return $this;
    }

    /**
     * @return $this
     */
    public function disablePagination()
    {
        $this->setPage(1);
        $this->pagination = false;

        return $this;
    }

    protected function createPaginator()
    {
        if ($this->entityName === Node::class ||
            $this->entityName === 'RZ\Roadiz\Core\Entities\Node' ||
            $this->entityName === '\RZ\Roadiz\Core\Entities\Node' ||
            $this->entityName === "Node") {
            $this->paginator = new NodePaginator(
                $this->entityManager,
                $this->entityName,
                $this->itemPerPage,
                $this->filteringArray
            );
            $this->paginator->setTranslation($this->translation);
        } elseif ($this->entityName == NodesSources::class ||
            $this->entityName == 'RZ\Roadiz\Core\Entities\NodesSources' ||
            $this->entityName == '\RZ\Roadiz\Core\Entities\NodesSources' ||
            $this->entityName == "NodesSources" ||
            strpos($this->entityName, NodeType::getGeneratedEntitiesNamespace()) !== false) {
            $this->paginator = new NodesSourcesPaginator(
                $this->entityManager,
                $this->entityName,
                $this->itemPerPage,
                $this->filteringArray
            );
        } else {
            $this->paginator = new Paginator(
                $this->entityManager,
                $this->entityName,
                $this->itemPerPage,
                $this->filteringArray
            );
        }

        $this->paginator->setDisplayingNotPublishedNodes($this->isDisplayingNotPublishedNodes());
        $this->paginator->setDisplayingAllNodesStatuses($this->isDisplayingAllNodesStatuses());
    }

    /**
     * Get Twig assignation to render list details.
     *
     * ** Fields:
     *
     * * description [string]
     * * search [string]
     * * currentPage [int]
     * * pageCount [int]
     * * itemPerPage [int]
     * * itemCount [int]
     * * previousPage [int]
     * * nextPage [int]
     * * nextPageQuery [string]
     * * previousPageQuery [string]
     * * previousQueryArray [array]
     * * nextQueryArray [array]
     *
     * @return array
     */
    public function getAssignation()
    {
        $assign = [
            'description' => '',
            'search' => $this->searchPattern,
            'currentPage' => $this->currentPage,
            'pageCount' => $this->getPageCount(),
            'itemPerPage' => $this->itemPerPage,
            'itemCount' => $this->getItemCount(),
            'nextPageQuery' => null,
            'previousPageQuery' => null,
        ];

        if ($this->getPageCount() > 1) {
            $assign['firstPageQuery'] = http_build_query(array_merge(
                $this->queryArray,
                ['page' => 1]
            ));
            $assign['lastPageQuery'] = http_build_query(array_merge(
                $this->queryArray,
                ['page' => $this->getPageCount()]
            ));
        }

        // compute next and prev page URL
        if ($this->currentPage > 1) {
            $this->queryArray['page'] = $this->currentPage - 1;
            $assign['previousPageQuery'] = http_build_query($this->queryArray);
            $assign['previousQueryArray'] = $this->queryArray;
            $assign['previousPage'] = $this->currentPage - 1;
        }
        // compute next and prev page URL
        if ($this->currentPage < $this->getPageCount()) {
            $this->queryArray['page'] = $this->currentPage + 1;
            $assign['nextPageQuery'] = http_build_query($this->queryArray);
            $assign['nextQueryArray'] = $this->queryArray;
            $assign['nextPage'] = $this->currentPage + 1;
        }

        return $assign;
    }

    /**
     * @return int
     */
    public function getItemCount()
    {
        if ($this->pagination === true &&
            null !== $this->paginator) {
            return $this->paginator->getTotalCount();
        }

        return 0;
    }

    /**
     * @return float|int
     */
    protected function getPageCount()
    {
        if ($this->pagination === true &&
            null !== $this->paginator) {
            return $this->paginator->getPageCount();
        }

        return 1;
    }

    /**
     * Return filtered entities.
     *
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities()
    {
        if ($this->pagination === true && null !== $this->paginator) {
            $this->paginator->setItemsPerPage($this->getItemPerPage());
            return $this->paginator->findByAtPage($this->orderingArray, $this->currentPage);
        } else {
            $repository = $this->entityManager->getRepository($this->entityName);
            if ($repository instanceof StatusAwareRepository) {
                $repository->setDisplayingNotPublishedNodes($this->isDisplayingNotPublishedNodes());
                $repository->setDisplayingAllNodesStatuses($this->isDisplayingAllNodesStatuses());
            }
            return $repository->findBy(
                $this->filteringArray,
                $this->orderingArray,
                $this->itemPerPage
            );
        }
    }

    /**
     * @return int
     */
    protected function getItemPerPage()
    {
        return $this->itemPerPage;
    }

    /**
     * Configure a custom item count per page.
     *
     * @param int $itemPerPage
     *
     * @return $this
     */
    public function setItemPerPage($itemPerPage)
    {
        if ($itemPerPage < 1) {
            throw new \RuntimeException("Item count per page cannot be lesser than 1.", 1);
        }

        $this->itemPerPage = (int) $itemPerPage;

        return $this;
    }

    /**
     * @return int
     */
    protected function getPage()
    {
        return $this->currentPage;
    }
}
