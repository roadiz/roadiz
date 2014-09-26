<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file Paginator.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

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

    /**
     * @param Doctrine\ORM\EntityManager $em          Entity manager
     * @param string                     $entityName   Full qualified entity classname
     * @param integer                    $itemPerPages Item par pages
     * @param array                      $criteria     Force selection criteria
     */
    public function __construct(
        EntityManager $em,
        $entityName,
        $itemPerPages = 10,
        array $criteria = array()
    ) {
        $this->em = $em;
        $this->entityName = $entityName;
        $this->setItemsPerPage($itemPerPages);
        $this->criteria = $criteria;

        if ($this->entityName == "") {
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
     * Return page count according to criteria.
     *
     * **Warning** : EntityRepository must implements *countBy* method
     *
     * @return integer
     */
    public function getPageCount()
    {
        if ($this->searchPattern !== null) {
            $total = $this->em->getRepository($this->entityName)
                            ->countSearchBy($this->searchPattern, $this->criteria);
        } else {
            $total = $this->em->getRepository($this->entityName)
                            ->countBy($this->criteria);
        }

        return ceil($total / $this->getItemsPerPage());
    }

    /**
     * Return entities filtered for current page.
     *
     * @param array   $order
     * @param integer $page
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByAtPage(array $order = array(), $page = 1)
    {
        if ($this->searchPattern !== null) {
            return $this->em->getRepository($this->entityName)
                        ->searchBy(
                            $this->searchPattern,
                            $this->criteria,
                            $order,
                            $this->getItemsPerPage(),
                            $this->getItemsPerPage() * ($page - 1)
                        );
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
