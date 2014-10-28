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

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;
use Themes\Rozier\Widgets\AbstractWidget;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Node tree according to Node hierarchy and given options.
 *
 * {@inheritdoc}
 */
class NodeTreeWidget extends AbstractWidget
{
    protected $parentNode =  null;
    protected $nodes =       null;
    protected $translation = null;
    protected $stackTree =   false;

    /**
     * @param Request                            $request           Current kernel request
     * @param AppController                      $refereeController Calling controller
     * @param RZ\Renzo\Core\Entities\Node        $parent            Entry point of NodeTreeWidget, set null if it's root
     * @param RZ\Renzo\Core\Entities\Translation $translation       NodeTree translation
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

        $this->getNodeTreeAssignationForParent();
    }


    /**
     * @return boolean
     */
    public function isStackTree() {
        return $this->stackTree;
    }

    /**
     * @param boolean $newstackTree
     */
    public function setStackTree($newstackTree) {
        $this->stackTree = (boolean) $newstackTree;

        return $this;
    }

    /**
     * Fill twig assignation array with NodeTree entities.
     */
    protected function getNodeTreeAssignationForParent()
    {
        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findOneBy(array('defaultTranslation'=>true));
        }

        $this->nodes = $this->getController()->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Node')
                ->findByParentWithTranslation($this->translation, $this->parentNode);
    }

    /**
     * @param RZ\Renzo\Core\Entities\Node $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenNodes(Node $parent)
    {
        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findOneBy(array('defaultTranslation'=>true));
        }
        if ($parent !== null) {
            return $this->nodes = $this->getController()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Node')
                    ->findBy(
                        array(
                            'parent' => $parent,
                            'status' => array('<=', Node::PUBLISHED),
                        ),
                        array('position'=>'ASC'),
                        null,
                        null,
                        $this->translation
                    );
        }

        return null;
    }
    /**
     * @return RZ\Renzo\Core\Entities\Node
     */
    public function getRootNode()
    {
        return $this->parentNode;
    }
    /**
     * @return RZ\Renzo\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @return ArrayCollection
     */
    public function getNodes()
    {
        return $this->nodes;
    }
}
