<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file FolderTreeWidget.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Renzo\Core\Entities\Folder;
use RZ\Renzo\Core\Kernel;
use Themes\Rozier\Widgets\AbstractWidget;

use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Folder tree according to Folder hierarchy and given options.
 */
class FolderTreeWidget extends AbstractWidget
{
    protected $parentFolder =  null;
    protected $folders =       null;

    /**
     * @param Request                    $request
     * @param AppController              $refereeController
     * @param RZ\Renzo\Core\Entities\Folder $parent
     */
    public function __construct(
        Request $request,
        $refereeController,
        Folder $parent = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentFolder = $parent;
        $this->getFolderTreeAssignationForParent();
    }

    /**
     * Fill twig assignation array with FolderTree entities.
     */
    protected function getFolderTreeAssignationForParent()
    {
        $this->folders = $this->getController()->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Folder')
                ->findBy(array('parent'=>$this->parentFolder));
    }

    /**
     * @param RZ\Renzo\Core\Entities\Folder $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenFolders(Folder $parent)
    {
        return $this->folders = $this->getController()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Folder')
                    ->findBy(array('parent'=>$parent));
    }
    /**
     * @return RZ\Renzo\Core\Entities\Folder
     */
    public function getRootFolder()
    {
        return $this->parentFolder;
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
    public function getFolders()
    {
        return $this->folders;
    }
}
