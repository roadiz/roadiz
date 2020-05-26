<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use RZ\Roadiz\CMS\Controllers\Controller;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Folder tree according to Folder hierarchy and given options.
 */
class FolderTreeWidget extends AbstractWidget
{
    protected $parentFolder = null;
    protected $translation = null;
    protected $folders = null;

    /**
     * @param Request    $request
     * @param Controller $refereeController
     * @param Folder     $parent
     */
    public function __construct(
        Request $request,
        Controller $refereeController,
        Folder $parent = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentFolder = $parent;
        $this->translation = $this->getController()->get('em')
            ->getRepository(Translation::class)
            ->findOneBy(['defaultTranslation' => true]);
        $this->getFolderTreeAssignationForParent();
    }

    /**
     * Fill twig assignation array with FolderTree entities.
     */
    protected function getFolderTreeAssignationForParent()
    {
        $this->folders = $this->getController()->get('em')
             ->getRepository(Folder::class)
             ->findByParentAndTranslation($this->parentFolder, $this->translation);
    }

    /**
     * @param Folder $parent
     * @return array
     */
    public function getChildrenFolders(Folder $parent)
    {
        return $this->folders = $this->getController()->get('em')
                    ->getRepository(Folder::class)
                    ->findByParentAndTranslation($parent, $this->translation);
    }
    /**
     * @return Folder
     */
    public function getRootFolder()
    {
        return $this->parentFolder;
    }

    /**
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }
}
