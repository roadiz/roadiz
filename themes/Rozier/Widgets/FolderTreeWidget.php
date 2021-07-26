<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Folder tree according to Folder hierarchy and given options.
 */
final class FolderTreeWidget extends AbstractWidget
{
    protected ?Folder $parentFolder = null;
    /**
     * @var array<Folder>|Paginator<Folder>|null
     */
    protected $folders = null;

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param Folder|null $parent
     */
    public function __construct(
        Request $request,
        EntityManagerInterface $entityManager,
        ?Folder $parent = null
    ) {
        parent::__construct($request, $entityManager);
        $this->parentFolder = $parent;
    }

    /**
     * @param Folder $parent
     * @return array
     */
    public function getChildrenFolders(Folder $parent): array
    {
        return $this->folders = $this->getEntityManager()
                    ->getRepository(Folder::class)
                    ->findByParentAndTranslation($parent, $this->getTranslation());
    }
    /**
     * @return Folder|null
     */
    public function getRootFolder(): ?Folder
    {
        return $this->parentFolder;
    }

    /**
     * @return array<Folder>|Paginator<Folder>
     */
    public function getFolders()
    {
        if (null === $this->folders) {
            $this->folders = $this->getEntityManager()
                ->getRepository(Folder::class)
                ->findByParentAndTranslation($this->getRootFolder(), $this->getTranslation());
        }
        return $this->folders;
    }
}
