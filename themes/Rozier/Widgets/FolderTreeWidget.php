<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Folder tree according to Folder hierarchy and given options.
 */
final class FolderTreeWidget extends AbstractWidget
{
    protected $parentFolder = null;
    /**
     * @var Translation|null
     */
    protected $translation = null;
    protected $folders = null;

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param Folder|null $parent
     */
    public function __construct(
        Request $request,
        EntityManagerInterface $entityManager,
        Folder $parent = null
    ) {
        parent::__construct($request, $entityManager);

        $this->parentFolder = $parent;
        $this->translation = $this->entityManager
            ->getRepository(Translation::class)
            ->findOneBy(['defaultTranslation' => true]);
        $this->getFolderTreeAssignationForParent();
    }

    /**
     * Fill twig assignation array with FolderTree entities.
     */
    protected function getFolderTreeAssignationForParent()
    {
        $this->folders = $this->entityManager
             ->getRepository(Folder::class)
             ->findByParentAndTranslation($this->parentFolder, $this->translation);
    }

    /**
     * @param Folder $parent
     * @return array
     */
    public function getChildrenFolders(Folder $parent)
    {
        return $this->folders = $this->entityManager
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
