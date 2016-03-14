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

/**
 * A simple paginator class to filter entities with limit and search.
 */
class Paginator
{
    protected $itemsPerPage;
    protected $itemCount;
    protected $entityName;
    protected $criteria;
    protected $searchPattern = null;
    protected $em;
    protected $totalCount = null;

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

        if ("" == $this->entityName) {
            throw new \RuntimeException("Entity name could not be empty", 1);
        }
        if ($this->itemsPerPage < 1) {
            throw new \RuntimeException("Items par page could not be lesser than 1.", 1);
        }
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
            if (null !== $this->searchPattern) {
                $this->totalCount = $this->em->getRepository($this->entityName)
                    ->countSearchBy($this->searchPattern, $this->criteria);
            } else {
                $this->totalCount = $this->em->getRepository($this->entityName)
                    ->countBy($this->criteria);
            }
        }

        return $this->totalCount;
    }

    /**
     * Return page count according to criteria.
     *
     * **Warning** : EntityRepository must implements *countBy* method
     *
     * @return float
     */
    public function getPageCount()
    {
        return ceil($this->getTotalCount() / $this->getItemsPerPage());
    }

    /**
     * Return entities filtered for current page.
     *
     * @param array   $order
     * @param integer $page
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByAtPage(array $order = [], $page = 1)
    {
        if (null !== $this->searchPattern) {
            return $this->searchByAtPage($order, $page);
        } else {
            return $this->em->getRepository($this->entityName)
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
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function searchByAtPage(array $order = [], $page = 1)
    {
        return $this->em->getRepository($this->entityName)
            ->searchBy(
                $this->searchPattern,
                $this->criteria,
                $order,
                $this->getItemsPerPage(),
                $this->getItemsPerPage() * ($page - 1)
            );
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
}
