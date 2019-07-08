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
 * @file Folder.php
 * @author Ambroise Maupate
 */
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
     * @var ArrayCollection
     * @Serializer\Groups({"folder"})
     */
    protected $children;
    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Document", inversedBy="folders")
     * @ORM\JoinTable(name="documents_folders")
     * @var ArrayCollection
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
     * @var ArrayCollection
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
     * @return ArrayCollection<DocumentInterface>
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
     * @return mixed
     */
    public function getTranslatedFolders(): Collection
    {
        return $this->translatedFolders;
    }

    /**
     * @param mixed $translatedFolders
     * @return Folder
     */
    public function setTranslatedFolders(Collection $translatedFolders)
    {
        $this->translatedFolders = $translatedFolders;
        return $this;
    }

    /**
     * @param Translation $translation
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslatedFoldersByTranslation(Translation $translation): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('translation', $translation));

        return $this->translatedFolders->matching($criteria);
    }

    /**
     * @return string
     * @deprecated Use getFolderName() method instead to differenciate from FolderTranslation’ name.
     */
    public function getName()
    {
        return $this->getFolderName();
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
        $this->folderName = StringHandler::slugify($folderName);
        return $this;
    }

    /**
     * @param string $folderName
     * @return Folder
     * @deprecated Use setFolderName() method instead to differenciate from FolderTranslation’ name.
     */
    public function setName($folderName)
    {
        return $this->setFolderName($folderName);
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
