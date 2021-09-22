<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\ListManagers;

/**
 * A paginator class to filter node-sources entities with limit and search.
 *
 * This class add authorizationChecker filters
 */
class NodesSourcesPaginator extends Paginator
{
    /**
     * {@inheritdoc}
     */
    public function getTotalCount(): int
    {
        if (null === $this->totalCount) {
            if (null !== $this->searchPattern) {
                $this->totalCount = $this->getRepository()->countSearchBy($this->searchPattern, $this->criteria);
            } else {
                $this->totalCount = $this->getRepository()->countBy($this->criteria);
            }
        }

        return $this->totalCount;
    }

    /**
     * Return entities filtered for current page.
     *
     * @param array   $order
     * @param integer $page
     *
     * @return array
     */
    public function findByAtPage(array $order = [], int $page = 1)
    {
        if (null !== $this->searchPattern) {
            return $this->searchByAtPage($order, $page);
        } else {
            return $this->getRepository()->findBy(
                $this->criteria,
                $order,
                $this->getItemsPerPage(),
                $this->getItemsPerPage() * ($page - 1)
            );
        }
    }
}
