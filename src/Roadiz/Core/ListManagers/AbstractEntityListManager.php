<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManagerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractEntityListManager implements EntityListManagerInterface
{
    /**
     * @var null|Request
     */
    protected $request = null;
    /**
     * @var bool
     */
    protected $pagination = true;
    /**
     * @var array|null
     */
    protected $queryArray = null;
    /**
     * @var int|null
     */
    protected $currentPage = null;
    /**
     * @var int|null
     */
    protected $itemPerPage = null;
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
     */
    public function __construct(?Request $request)
    {
        $this->request = $request;
        $this->displayNotPublishedNodes = false;
        $this->displayAllNodesStatuses = false;
        if (null !== $request) {
            $this->queryArray = array_filter($request->query->all());
        } else {
            $this->queryArray = [];
        }
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
     * @return EntityListManagerInterface
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
     * @return EntityListManagerInterface
     */
    public function setDisplayingAllNodesStatuses($displayAllNodesStatuses)
    {
        $this->displayAllNodesStatuses = $displayAllNodesStatuses;
        return $this;
    }

    /**
     * @inheritDoc
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
     * @return int
     */
    protected function getPage()
    {
        return $this->currentPage;
    }

    /**
     * @return EntityListManagerInterface
     */
    public function enablePagination()
    {
        $this->pagination = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disablePagination()
    {
        $this->setPage(1);
        $this->pagination = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAssignation()
    {
        $assign = [
            'currentPage' => $this->getPage(),
            'pageCount' => $this->getPageCount(),
            'itemPerPage' => $this->getItemPerPage(),
            'itemCount' => $this->getItemCount(),
            'nextPageQuery' => null,
            'previousPageQuery' => null,
        ];

        if ($this->getPageCount() > 1) {
            $assign['firstPageQuery'] = http_build_query(array_merge(
                $this->getQueryString(),
                ['page' => 1]
            ));
            $assign['lastPageQuery'] = http_build_query(array_merge(
                $this->getQueryString(),
                ['page' => $this->getPageCount()]
            ));
        }

        // compute next and prev page URL
        if ($this->currentPage > 1) {
            $previousQueryString = array_merge(
                $this->getQueryString(),
                ['page' => $this->getPage() - 1]
            );
            $assign['previousPageQuery'] = http_build_query($previousQueryString);
            $assign['previousQueryArray'] = $previousQueryString;
            $assign['previousPage'] = $this->getPage() - 1;
        }
        // compute next and prev page URL
        if ($this->getPage() < $this->getPageCount()) {
            $nextQueryString = array_merge(
                $this->getQueryString(),
                ['page' => $this->getPage() + 1]
            );
            $assign['nextPageQuery'] = http_build_query($nextQueryString);
            $assign['nextQueryArray'] = $nextQueryString;
            $assign['nextPage'] = $this->getPage() + 1;
        }

        return $assign;
    }

    protected function getQueryString(): array
    {
        return $this->queryArray ?? [];
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
     * @return EntityListManagerInterface
     */
    public function setItemPerPage($itemPerPage)
    {
        if ($itemPerPage < 1) {
            throw new \RuntimeException("Item count per page cannot be lesser than 1.", 1);
        }

        $this->itemPerPage = (int) $itemPerPage;

        return $this;
    }
}
