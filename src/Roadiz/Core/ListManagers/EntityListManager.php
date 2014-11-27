<?php
/**
 * Copyright © 2014, REZO ZERO
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file EntityListManager.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\ListManagers;

use RZ\Roadiz\Core\Utils\Paginator;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManager;

/**
 * Perform basic filtering and search over entity listings.
 */
class EntityListManager
{
    const ITEM_PER_PAGE = 20;

    protected $request = null;
    protected $_em = null;
    protected $entityName;
    protected $paginator = null;

    protected $pagination = true;

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
        $this->queryArray = $request->query->all();

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

    public function enablePagination()
    {
        $this->pagination = true;
    }

    public function disablePagination()
    {
        $this->pagination = false;
    }

    /**
     * Handle request to find filter to apply to entity listing.
     *
     * @param boolean $disabled Disable pagination and filtering over GET params
     *
     * @return void
     */
    public function handle($disabled = false)
    {
        $this->paginator = new Paginator(
            $this->_em,
            $this->entityName,
            $this->itemPerPage,
            $this->filteringArray
        );

        if (false === $disabled) {
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
        } else {
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
        $assign = array(
            'description'       => '',
            'search'            => $this->searchPattern,
            'currentPage'       => $this->currentPage,
            'pageCount'         => $this->paginator->getPageCount(),
            'itemPerPage'       => $this->itemPerPage,
            'itemCount'         => $this->_em
                                        ->getRepository($this->entityName)
                                        ->countBy($this->filteringArray),
            'nextPageQuery'     => null,
            'previousPageQuery' => null
        );

        // Edit item count after a search
        if ($this->searchPattern != '') {
            $assign['itemCount'] = $this->_em
                ->getRepository($this->entityName)
                ->countSearchBy($this->searchPattern, $this->filteringArray);
        }


        // compute next and prev page URL
        if ($this->currentPage > 1) {
            $this->queryArray['page'] = $this->currentPage - 1;
            $assign['previousPageQuery'] = http_build_query($this->queryArray);
            $assign['previousPage'] = $this->currentPage - 1;
        }
        // compute next and prev page URL
        if ($this->currentPage < $this->paginator->getPageCount()) {
            $this->queryArray['page'] = $this->currentPage + 1;
            $assign['nextPageQuery'] = http_build_query($this->queryArray);
            $assign['nextPage'] = $this->currentPage + 1;
        }

        return $assign;
    }

    /**
     * Return filtered entities.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getEntities()
    {
        if ($this->pagination == true) {
            return $this->paginator->findByAtPage($this->orderingArray, $this->currentPage);
        } else {
            return $this->_em->getRepository($this->entityName)
                            ->findBy(
                                $this->filteringArray,
                                $this->orderingArray,
                                $this->itemPerPage
                            );
        }
    }
}
