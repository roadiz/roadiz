<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

use Symfony\Component\HttpFoundation\Request;

abstract class AbstractEntityListManager implements EntityListManagerInterface
{
    protected ?Request $request = null;
    protected bool $pagination = true;
    protected ?array $queryArray = null;
    protected ?int $currentPage = null;
    protected ?int $itemPerPage = null;
    protected bool $displayNotPublishedNodes;
    protected bool $displayAllNodesStatuses;
    protected bool $allowRequestSorting = true;
    protected bool $allowRequestSearching = true;

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

    public function setAllowRequestSorting(bool $allowRequestSorting)
    {
        $this->allowRequestSorting = $allowRequestSorting;
        return $this;
    }

    public function setAllowRequestSearching(bool $allowRequestSearching)
    {
        $this->allowRequestSearching = $allowRequestSearching;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisplayingNotPublishedNodes(): bool
    {
        return $this->displayNotPublishedNodes;
    }

    /**
     * @param bool $displayNotPublishedNodes
     * @return EntityListManagerInterface
     */
    public function setDisplayingNotPublishedNodes(bool $displayNotPublishedNodes)
    {
        $this->displayNotPublishedNodes = $displayNotPublishedNodes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisplayingAllNodesStatuses(): bool
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
    public function setDisplayingAllNodesStatuses(bool $displayAllNodesStatuses)
    {
        $this->displayAllNodesStatuses = $displayAllNodesStatuses;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setPage(int $page)
    {
        $this->currentPage = $page > 0 ? $page : 1;

        return $this;
    }

    /**
     * @return int
     */
    protected function getPage(): int
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
    public function getAssignation(): array
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
    protected function getItemPerPage(): int
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
    public function setItemPerPage(int $itemPerPage)
    {
        $this->itemPerPage = $itemPerPage > 0 ? $itemPerPage : 1;

        return $this;
    }

    /**
     * @return int
     */
    public function getPageCount(): int
    {
        return (int) ceil($this->getItemCount() / $this->getItemPerPage());
    }
}
