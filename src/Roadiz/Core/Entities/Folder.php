<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimedPositioned;
use RZ\Roadiz\Core\AbstractEntities\LeafTrait;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\FolderInterface;
use RZ\Roadiz\Utils\StringHandler;
use JMS\Serializer\Annotation as Serializer;

/**
 * Folders entity represent a directory on server with datetime and naming.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\FolderRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="folders", indexes={
 *     @ORM\Index(columns={"visible"}),
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"created_at"}),
 *     @ORM\Index(columns={"updated_at"})
 * })
 */
class Folder extends AbstractDateTimedPositioned implements FolderInterface
{
    use LeafTrait;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Folder", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Folder|null
     * @Serializer\Exclude
     */
    protected $parent = null;
    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Folder", mappedBy="parent", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @var Collection<Folder>
     * @Serializer\Groups({"folder"})
     */
    protected $children;
    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Document", inversedBy="folders")
     * @ORM\JoinTable(name="documents_folders")
     * @var Collection<Document>
     * @Serializer\Groups({"folder"})
     */
    protected $documents;
    /**
     * @ORM\Column(name="folder_name", type="string", unique=true, nullable=false)
     * @var string
     * @Serializer\Groups({"folder", "document"})
     */
    private $folderName = '';
    /**
     * @var string
     * @Serializer\Exclude()
     */
    private $dirtyFolderName = '';
    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @var boolean
     * @Serializer\Groups({"folder"})
     */
    private $visible = true;
    /**
     * @ORM\OneToMany(targetEntity="FolderTranslation", mappedBy="folder", orphanRemoval=true)
     * @var Collection<FolderTranslation>
     * @Serializer\Groups({"folder", "document"})
     */
    private $translatedFolders;

    /**
     * Create a new Folder.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->translatedFolders = new ArrayCollection();
        $this->initAbstractDateTimed();
    }

    /**
     * @param DocumentInterface $document
     * @return $this
     */
    public function addDocument(DocumentInterface $document)
    {
        if (!$this->getDocuments()->contains($document)) {
            $this->documents->add($document);
        }

        return $this;
    }

    /**
     * @return Collection<Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * @param DocumentInterface $document
     * @return $this
     */
    public function removeDocument(DocumentInterface $document)
    {
        if ($this->getDocuments()->contains($document)) {
            $this->documents->removeElement($document);
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param boolean $visible
     * @return Folder
     */
    public function setVisible($visible)
    {
        $this->visible = (boolean) $visible;
        return $this;
    }

    /**
     * @return Collection<FolderTranslation>
     */
    public function getTranslatedFolders(): Collection
    {
        return $this->translatedFolders;
    }

    /**
     * @param Collection<FolderTranslation> $translatedFolders
     * @return Folder
     */
    public function setTranslatedFolders(Collection $translatedFolders)
    {
        $this->translatedFolders = $translatedFolders;
        return $this;
    }

    /**
     * @param Translation $translation
     * @return Collection<FolderTranslation>
     */
    public function getTranslatedFoldersByTranslation(Translation $translation): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('translation', $translation));

        return $this->translatedFolders->matching($criteria);
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
    }

    /**
     * @param string $folderName
     * @return Folder
     */
    public function setFolderName($folderName)
    {
        $this->dirtyFolderName = $folderName;
        $this->folderName = StringHandler::slugify($folderName ?? '');
        return $this;
    }

    /**
     * @return string|null
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"folder"})
     */
    public function getName(): ?string
    {
        return $this->getTranslatedFolders()->first() ?
            $this->getTranslatedFolders()->first()->getName() :
            $this->getFolderName();
    }

    /**
     * @return string
     */
    public function getDirtyFolderName()
    {
        return $this->dirtyFolderName;
    }

    /**
     * @param string $dirtyFolderName
     * @return Folder
     */
    public function setDirtyFolderName($dirtyFolderName)
    {
        $this->dirtyFolderName = $dirtyFolderName;
        return $this;
    }

    /**
     * Get folder full path using folder names.
     *
     * @return string
     */
    public function getFullPath(): string
    {
        $parents = $this->getParents();
        $path = [];

        /** @var Folder $parent */
        foreach ($parents as $parent) {
            $path[] = $parent->getFolderName();
        }

        $path[] = $this->getFolderName();

        return implode('/', $path);
    }
}
