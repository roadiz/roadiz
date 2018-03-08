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
 *
 * @file NodeTreeWidget.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\CMS\Controllers\Controller;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * Prepare a Node tree according to Node hierarchy and given options.
 *
 * {@inheritdoc}
 */
class NodeTreeWidget extends AbstractWidget
{
    const SESSION_ITEM_PER_PAGE = 'nodetree_item_per_page';

    protected $parentNode = null;
    protected $nodes = null;
    protected $tag = null;
    protected $translation = null;
    protected $availableTranslations = null;
    protected $stackTree = false;
    protected $filters = null;
    protected $canReorder = true;

    /**
     * @param Request     $request           Current kernel request
     * @param Controller  $refereeController Calling controller
     * @param Node        $parent            Entry point of NodeTreeWidget, set null if it's root
     * @param Translation $translation       NodeTree translation
     */
    public function __construct(
        Request $request,
        Controller $refereeController,
        Node $parent = null,
        Translation $translation = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentNode = $parent;
        $this->translation = $translation;

        if ($this->translation === null) {
            $this->translation = $this->getController()->get('defaultTranslation');
        }

        $this->availableTranslations = $this->getController()->get('em')
             ->getRepository(Translation::class)
             ->findBy([], [
                 'defaultTranslation' => 'DESC',
                 'locale' => 'ASC',
             ]);
    }

    /**
     * @return \RZ\Roadiz\Core\Entities\Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Tag $tag
     *
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isStackTree()
    {
        return $this->stackTree;
    }

    /**
     * @param boolean $newstackTree
     *
     * @return $this
     */
    public function setStackTree($newstackTree)
    {
        $this->stackTree = (boolean) $newstackTree;

        return $this;
    }

    /**
     * Fill twig assignation array with NodeTree entities.
     */
    protected function getRootListManager()
    {
        return $this->getListManager($this->parentNode);
    }

    /**
     * @param Node|null $parent
     * @param bool $subRequest Default: false
     * @return \RZ\Roadiz\Core\ListManagers\EntityListManager
     */
    protected function getListManager(Node $parent = null, $subRequest = false)
    {
        $criteria = [
            'parent' => $parent,
            'translation' => $this->translation,
        ];

        if (null !== $this->tag) {
            $criteria['tags'] = $this->tag;
        }

        $ordering = [
            'position' => 'ASC',
        ];

        if (null !== $parent &&
            $parent->getChildrenOrder() !== 'order' &&
            $parent->getChildrenOrder() !== 'position') {
            $ordering = [
                $parent->getChildrenOrder() => $parent->getChildrenOrderDirection(),
            ];

            $this->canReorder = false;
        }

        /*
         * Manage get request to filter list
         */
        $listManager = $this->controller->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Node',
            $criteria,
            $ordering
        );
        $listManager->setDisplayingNotPublishedNodes(true);

        if (true === $this->stackTree) {
            $listManager->setItemPerPage(20);
            $listManager->handle();

            /*
             * Stored in session
             */
            $sessionListFilter = new SessionListFilters(static::SESSION_ITEM_PER_PAGE);
            $sessionListFilter->handleItemPerPage($this->request, $listManager);
        } else {
            $listManager->setItemPerPage(99999);
            $listManager->handle(true);
        }


        if ($subRequest) {
            $listManager->disablePagination();
        }

        return $listManager;
    }

    /**
     * @param Node $parent
     * @param bool $subRequest Default: false
     * @return array
     */
    public function getChildrenNodes(Node $parent = null, $subRequest = false)
    {
        return $this->getListManager($parent, $subRequest)->getEntities();
    }
    /**
     * @return Node
     */
    public function getRootNode()
    {
        return $this->parentNode;
    }

    /**
     * Get entity list manager filters.
     *
     * Call getNodes() first to populate this.
     *
     * @return array|null
     */
    public function getFilters()
    {
        return $this->filters;
    }
    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @return array
     */
    public function getAvailableTranslations()
    {
        return $this->availableTranslations;
    }

    /**
     * @return ArrayCollection
     */
    public function getNodes()
    {
        if (null === $this->nodes) {
            $manager = $this->getRootListManager();
            $this->nodes = $manager->getEntities();
            $this->filters = $manager->getAssignation();
        }

        return $this->nodes;
    }

    /**
     * Gets the value of canReorder.
     *
     * @return boolean
     */
    public function getCanReorder()
    {
        return $this->canReorder;
    }
}
