<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file Paginator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\ListManagers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Repositories\EntityRepository;

/**
 * A simple paginator class to filter entities with limit and search.
 */
class Paginator
{
    /**
     * @var int
     */
    protected $itemsPerPage;
    /**
     * @var int
     */
    protected $itemCount;
    /**
     * @var string
     */
    protected $entityName;
    /**
     * @var array
     */
    protected $criteria;
    /**
     * @var null|string
     */
    protected $searchPattern = null;
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var null|int
     */
    protected $totalCount = null;

    /**
     * @var bool
     */
    protected $displayNotPublishedNodes;

    /**
     * @var bool
     */
    protected $displayAllNodesStatuses;

    /**
     * @param EntityManager $em           Entity manager
     * @param string        $entityName   Full qualified entity classname
     * @param integer       $itemPerPages Item par pages
     * @param array         $criteria     Force selection criteria
     */
    public function __construct(
        EntityManager $em,
        $entityName,
        $itemPerPages = 10,
        array $criteria = []
    ) {
        $this->em = $em;
        $this->entityName = $entityName;
        $this->itemsPerPage = $itemPerPages;
        $this->criteria = $criteria;
        $this->displayNotPublishedNodes = false;
        $this->displayAllNodesStatuses = false;

        if ("" == $this->entityName) {
            throw new \RuntimeException("Entity name could not be empty", 1);
        }
        if ($this->itemsPerPage < 1) {
            throw new \RuntimeException("Items par page could not be lesser than 1.", 1);
        }
    }

    /**
     * @return bool
     */
    public function isDisplayingNotPublishedNodes()
    {
        return $this->displayNotPublishedNodes;
    }

    /**
     * @param bool $displayNonPublishedNodes
     * @return Paginator
     */
    public function setDisplayingNotPublishedNodes($displayNonPublishedNodes)
    {
        $this->displayNotPublishedNodes = $displayNonPublishedNodes;
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
     * @return string
     */
    public function getSearchPattern()
    {
        return $this->searchPattern;
    }

    /**
     * @param string $searchPattern
     *
     * @return $this
     */
    public function setSearchPattern($searchPattern)
    {
        $this->searchPattern = $searchPattern;

        return $this;
    }

    /**
     * Return total entities count for given criteria.
     *
     * @return int
     */
    public function getTotalCount()
    {
        if (null === $this->totalCount) {
            $repository = $this->getRepository();
            if ($repository instanceof EntityRepository) {
                if (null !== $this->searchPattern) {
                    $this->totalCount = $repository->countSearchBy($this->searchPattern, $this->criteria);
                } else {
                    $this->totalCount = $repository->countBy($this->criteria);
                }
            } else {
                throw new \RuntimeException('Count-by feature is not available using Doctrine default repository.');
            }
        }

        return $this->totalCount;
    }

    /**
     * Return page count according to criteria.
     *
     * **Warning** : EntityRepository must implements *countBy* method
     *
     * @return int
     */
    public function getPageCount()
    {
        return (int) ceil($this->getTotalCount() / $this->getItemsPerPage());
    }

    /**
     * Return entities filtered for current page.
     *
     * @param array   $order
     * @param integer $page
     *
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findByAtPage(array $order = [], $page = 1)
    {
        if (null !== $this->searchPattern) {
            return $this->searchByAtPage($order, $page);
        } else {
            return $this->getRepository()
                ->findBy(
                    $this->criteria,
                    $order,
                    $this->getItemsPerPage(),
                    $this->getItemsPerPage() * ($page - 1)
                );
        }
    }

    /**
     * Use a search query to paginate instead of a findBy.
     *
     * @param array   $order
     * @param integer $page
     *
     * @return array
     */
    public function searchByAtPage(array $order = [], $page = 1)
    {
        $repository = $this->getRepository();
        if ($repository instanceof EntityRepository) {
            return $repository->searchBy(
                $this->searchPattern,
                $this->criteria,
                $order,
                $this->getItemsPerPage(),
                $this->getItemsPerPage() * ($page - 1)
            );
        }

        throw new \RuntimeException('Search feature is not available using Doctrine default repository.');
    }

    /**
     * @param integer $itemsPerPage
     *
     * @return $this
     */
    public function setItemsPerPage($itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }
    /**
     * @return integer $itemsPerPage
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|EntityRepository
     */
    protected function getRepository()
    {
        $repository = $this->em->getRepository($this->entityName);
        if ($repository instanceof EntityRepository) {
            $repository->setDisplayingNotPublishedNodes($this->isDisplayingNotPublishedNodes());
            $repository->setDisplayingAllNodesStatuses($this->isDisplayingAllNodesStatuses());
        }
        return $repository;
    }
}
