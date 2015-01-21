<?php
/*
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
 * @file FolderTreeWidget.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Roadiz\Core\Entities\Folder;
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
     * @param RZ\Roadiz\Core\Entities\Folder $parent
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
                ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                ->findBy(
                    ['parent'=>$this->parentFolder],
                    ['position'=>'ASC']
                );
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Folder $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenFolders(Folder $parent)
    {
        return $this->folders = $this->getController()->getService('em')
                    ->getRepository('RZ\Roadiz\Core\Entities\Folder')
                    ->findBy(['parent'=>$parent], ['position'=>'ASC']);
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Folder
     */
    public function getRootFolder()
    {
        return $this->parentFolder;
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Translation
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
