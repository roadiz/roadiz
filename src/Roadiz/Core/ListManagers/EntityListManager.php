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
 * @file EntityListManager.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\ListManagers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Perform basic filtering and search over entity listings.
 *
 * @package RZ\Roadiz\Core\ListManagers
 */
class EntityListManager
{
    const ITEM_PER_PAGE = 20;

    /**
     * @var null|Request
     */
    protected $request = null;
    /**
     * @var EntityManager|null
     */
    protected $_em = null;
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
     * @var AuthorizationChecker|null
     */
    protected $authorizationChecker = null;
    /**
     * @var bool
     */
    protected $preview = false;

    /**
     * @param Request $request
     * @param EntityManager $_em
     * @param string $entityName
     * @param array $preFilters
     * @param array $preOrdering
     */
    public function __construct(
        Request $request,
        EntityManager $_em,
        $entityName,
        $preFilters = [],
        $preOrdering = []
    ) {
        $this->request = $request;
        $this->entityName = $entityName;
        $this->_em = $_em;

        $this->orderingArray = $preOrdering;
        $this->filteringArray = $preFilters;
        $this->assignation = [];
        $this->queryArray = array_filter($request->query->all());

        $this->itemPerPage = static::ITEM_PER_PAGE;

        // transform the key chroot in parent
        if (array_key_exists('chroot', $preFilters)) {
            if ($preFilters["chroot"] instanceof Node) {
                $ids = $preFilters["chroot"]->getHandler()->getAllOffspringId(); // get all offspringId
                if (array_key_exists('parent', $preFilters)) {
                    // test if parent key exist
                    if (is_array($preFilters["parent"])) {
                        // test if multiple parent id
                        if (count(array_intersect($preFilters["parent"], $ids))
                            != count($preFilters["parent"])) {
                            // test if all parent are in the chroot
                            $this->filteringArray["parent"] = -1; // -1 for make the search return []
                        }
                    } else {
                        if ($preFilters["parent"] instanceof Node) {
                            // make transforme all id in int
                            $parent = $preFilters["parent"]->getId();
                        } else {
                            $parent = (int) $preFilters["parent"];
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
     * @param Translation $newtranslation
     * @return $this
     */
    public function setTranslation(Translation $newtranslation = null)
    {
        $this->translation = $newtranslation;

        return $this;
    }

    /**
     * @return AuthorizationChecker
     */
    public function getAuthorizationChecker()
    {
        return $this->authorizationChecker;
    }

    /**
     * @param AuthorizationChecker $authorizationChecker
     * @return $this
     */
    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker = null)
    {
        $this->authorizationChecker = $authorizationChecker;

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

        if (false === $disabled) {
            if ($this->request->query->get('search') != "") {
                $this->paginator->setSearchPattern($this->request->query->get('search'));
            }
        }
    }

    /**
     * Configure a custom current page.
     *
     * @param integer $page
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
        if ($this->entityName == 'RZ\Roadiz\Core\Entities\Node' ||
            $this->entityName == '\RZ\Roadiz\Core\Entities\Node' ||
            $this->entityName == "Node") {
            $this->paginator = new NodePaginator(
                $this->_em,
                $this->entityName,
                $this->itemPerPage,
                $this->filteringArray
            );
            $this->paginator->setTranslation($this->translation);
            $this->paginator->setAuthorizationChecker($this->authorizationChecker);
            $this->paginator->setPreview($this->preview);
        } elseif ($this->entityName == 'RZ\Roadiz\Core\Entities\NodesSources' ||
            $this->entityName == '\RZ\Roadiz\Core\Entities\NodesSources' ||
            $this->entityName == "NodesSources" ||
            strpos($this->entityName, NodeType::getGeneratedEntitiesNamespace()) !== false) {
            $this->paginator = new NodesSourcesPaginator(
                $this->_em,
                $this->entityName,
                $this->itemPerPage,
                $this->filteringArray
            );

            $this->paginator->setAuthorizationChecker($this->authorizationChecker);
            $this->paginator->setPreview($this->preview);
        } else {
            $this->paginator = new Paginator(
                $this->_em,
                $this->entityName,
                $this->itemPerPage,
                $this->filteringArray
            );
        }
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
    public function getPageCount()
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
     * @return array
     */
    public function getEntities()
    {
        if ($this->pagination === true &&
            null !== $this->paginator) {
            $this->paginator->setItemsPerPage($this->getItemPerPage());
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

    /**
     * Gets the value of preview.
     *
     * @return boolean
     */
    public function isPreview()
    {
        return $this->preview;
    }

    /**
     * Sets the value of preview.
     *
     * @param boolean $preview the preview
     *
     * @return self
     */
    public function setPreview($preview)
    {
        $this->preview = (boolean) $preview;

        return $this;
    }

    /**
     * @return int
     */
    public function getItemPerPage()
    {
        return $this->itemPerPage;
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
     * @return int
     */
    public function getPage()
    {
        return $this->currentPage;
    }
}
