<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file TagTreeWidget.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;
use Themes\Rozier\Widgets\AbstractWidget;

use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Tag tree according to Tag hierarchy and given options.
 */
class TagTreeWidget extends AbstractWidget
{
    protected $parentTag =  null;
    protected $tags =       null;
    protected $translation = null;

    /**
     * @param Request                    $request
     * @param AppController              $refereeController
     * @param RZ\Renzo\Core\Entities\Tag $parent
     */
    public function __construct(
        Request $request,
        $refereeController,
        Tag $parent = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentTag = $parent;
        $this->getTagTreeAssignationForParent();
    }

    /**
     * Fill twig assignation array with TagTree entities.
     */
    protected function getTagTreeAssignationForParent()
    {
        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findOneBy(array('defaultTranslation'=>true));
        }

        $this->tags = $this->getController()->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Tag')
                ->findBy(array('parent'=>$this->parentTag), array('position'=>'ASC'));
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenTags(Tag $parent)
    {
        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findOneBy(array('defaultTranslation'=>true));
        }
        if ($parent !== null) {
            return $this->tags = $this->getController()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Tag')
                    ->findBy(array('parent'=>$parent), array('position'=>'ASC'));
        }

        return null;
    }
    /**
     * @return RZ\Renzo\Core\Entities\Tag
     */
    public function getRootTag()
    {
        return $this->parentTag;
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
    public function getTags()
    {
        return $this->tags;
    }
}
