<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file EntityListManager.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\ListManagers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManager;

/**
 * Perform basic filtering and search over entity listings.
 */
class EntityListManager
{
    const ITEM_PER_PAGE = 10;

    protected $request = null;
    protected $_em = null;
    protected $entityName;
    protected $paginator = null;

    protected $orderingArray = null;
    protected $filteringArray = null;
    protected $queryArray = null;
    protected $searchPattern = null;
    protected $currentPage = null;

    protected $assignation = null;
    protected $itemPerPage = null;

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param Doctrine\ORM\EntityManager               $_em
     * @param string                                   $entityName
     * @param array                                    $preFilters
     * @param array                                    $preOrdering
     */
    public function __construct(
        Request $request,
        EntityManager $_em,
        $entityName,
        $preFilters = array(),
        $preOrdering = array()
    ) {
        $this->request =    $request;
        $this->entityName = $entityName;
        $this->_em =        $_em;

        $this->orderingArray = $preOrdering;
        $this->filteringArray = $preFilters;
        $this->assignation = array();
        $this->queryArray = array();

        $this->itemPerPage = static::ITEM_PER_PAGE;
    }

    /**
     * Configure a custom item count per page.
     *
     * @param integer $itemPerPage
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
     * Handle request to find filter to apply to entity listing.
     *
     * @return void
     */
    public function handle()
    {
        $this->paginator = new \RZ\Renzo\Core\Utils\Paginator(
            $this->_em,
            $this->entityName,
            $this->itemPerPage,
            $this->filteringArray
        );

        if ($this->request->query->get('field') &&
            $this->request->query->get('ordering')) {

            $this->orderingArray[$this->request->query->get('field')] = $this->request->query->get('ordering');
            $this->queryArray['field'] = $this->request->query->get('field');
            $this->queryArray['ordering'] = $this->request->query->get('ordering');
        }

        if ($this->request->query->get('search') != "") {
            $this->searchPattern = $this->request->query->get('search');
            $this->queryArray['search'] = $this->request->query->get('search');
            $this->paginator->setSearchPattern($this->request->query->get('search'));
        }

        $this->currentPage = $this->request->query->get('page');
        if (!($this->currentPage > 1)) {
            $this->currentPage = 1;
        }
    }

    /**
     * Get Twig assignation to render list details.
     *
     * ## Fields:
     *
     * * description
     * * search
     * * currentPage
     * * pageCount
     * * itemPerPage
     * * itemCount
     *
     * @return array
     */
    public function getAssignation()
    {
        try {
            $assign = array(
                'description'       => '',
                'search'            => $this->searchPattern,
                'currentPage'       => $this->currentPage,
                'pageCount'         => $this->paginator->getPageCount(),
                'itemPerPage'       => $this->itemPerPage,
                'itemCount'         => $this->_em->getRepository($this->entityName)->countBy($this->filteringArray),
                'nextPageQuery'     => null,
                'previousPageQuery' => null
            );

            // Edit item count after a search
            try {
                if ($this->searchPattern != '') {
                    $assign['itemCount'] = $this->_em
                        ->getRepository($this->entityName)
                        ->countSearchBy($this->searchPattern, $this->filteringArray);
                }
            } catch (\Exception $e) {

            }

            // compute next and prev page URL
            if ($this->currentPage > 1) {
                $this->queryArray['page'] = $this->currentPage - 1;
                $assign['previousPageQuery'] = http_build_query($this->queryArray);
            }
            // compute next and prev page URL
            if ($this->currentPage < $this->paginator->getPageCount()) {
                $this->queryArray['page'] = $this->currentPage + 1;
                $assign['nextPageQuery'] = http_build_query($this->queryArray);
            }

            return $assign;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Return filtered entities.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getEntities()
    {
        try {
            return $this->paginator->findByAtPage($this->orderingArray, $this->currentPage);
        } catch (\Exception $e) {
            return null;
        }
    }
}
