<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

interface EntityListManagerInterface
{
    const ITEM_PER_PAGE = 20;

    /**
     * @return bool
     */
    public function isDisplayingNotPublishedNodes();

    /**
     * @param bool $displayNotPublishedNodes
     * @return EntityListManagerInterface
     */
    public function setDisplayingNotPublishedNodes($displayNotPublishedNodes);

    /**
     * @return bool
     */
    public function isDisplayingAllNodesStatuses();

    /**
     * Switch repository to disable any security on Node status. To use ONLY in order to
     * view deleted and archived nodes.
     *
     * @param bool $displayAllNodesStatuses
     * @return EntityListManagerInterface
     */
    public function setDisplayingAllNodesStatuses($displayAllNodesStatuses);

    /**
     * Handle request to find filter to apply to entity listing.
     *
     * @param boolean $disabled Disable pagination and filtering over GET params
     * @return void
     */
    public function handle($disabled = false);

    /**
     * Configure a custom current page.
     *
     * @param int $page
     *
     * @return EntityListManagerInterface
     */
    public function setPage($page);

    /**
     * @return EntityListManagerInterface
     */
    public function disablePagination();

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
    public function getAssignation();

    /**
     * @return int
     */
    public function getItemCount();

    /**
     * Return filtered entities.
     *
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities();

    /**
     * Configure a custom item count per page.
     *
     * @param int $itemPerPage
     *
     * @return EntityListManagerInterface
     */
    public function setItemPerPage($itemPerPage);
}
