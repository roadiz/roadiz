<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTreeWidget.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Themes\Rozier\Widgets\AbstractWidget;
use Symfony\Component\HttpFoundation\Request;

use RZ\Roadiz\Core\ListManagers\EntityListManager;

/**
 * Prepare a Node tree according to Node hierarchy and given options.
 *
 * {@inheritdoc}
 */
class NodeTreeWidget extends AbstractWidget
{
    protected $parentNode =            null;
    protected $nodes =                 null;
    protected $translation =           null;
    protected $availableTranslations = null;
    protected $stackTree =             false;
    protected $filters =               null;

    /**
     * @param Request                            $request           Current kernel request
     * @param AppController                      $refereeController Calling controller
     * @param RZ\Roadiz\Core\Entities\Node        $parent            Entry point of NodeTreeWidget, set null if it's root
     * @param RZ\Roadiz\Core\Entities\Translation $translation       NodeTree translation
     */
    public function __construct(
        Request $request,
        $refereeController,
        Node $parent = null,
        Translation $translation = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentNode = $parent;
        $this->translation = $translation;

        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                    ->findDefault();
        }

        $this->availableTranslations = $this->getController()->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                    ->findAll();
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

    protected function getListManager(Node $parent = null)
    {
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $this->request,
            $this->controller->getService('em'),
            'RZ\Roadiz\Core\Entities\Node',
            array(
                'parent' =>      $parent,
                'translation' => $this->translation,
                'status' =>      array('<=', Node::PUBLISHED),
            ),
            array('position'=>'ASC')
        );

        if (true === $this->stackTree) {
            $listManager->setItemPerPage(20);
            $listManager->handle();
        } else {
            $listManager->setItemPerPage(100);
            $listManager->handle(true);
        }

        return $listManager;
    }
    /**
     * @param RZ\Roadiz\Core\Entities\Node $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenNodes(Node $parent = null)
    {
        return $this->getListManager($parent)->getEntities();
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getRootNode()
    {
        return $this->parentNode;
    }

    public function getFilters()
    {
        return $this->filters;
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Translation
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
}
