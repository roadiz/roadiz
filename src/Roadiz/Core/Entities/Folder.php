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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimedPositioned;
use RZ\Roadiz\Core\AbstractEntities\LeafInterface;
use RZ\Roadiz\Core\AbstractEntities\LeafTrait;
use RZ\Roadiz\Core\Handlers\FolderHandler;
use RZ\Roadiz\Utils\StringHandler;

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
class Folder extends AbstractDateTimedPositioned implements LeafInterface
{
    use LeafTrait;

    /**
     * @ORM\Column(name="folder_name", type="string", unique=true, nullable=false)
     * @var string
     */
    private $folderName;

    /**
     * @var string
     */
    private $dirtyFolderName;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @var boolean
     */
    private $visible = true;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Folder", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Folder", mappedBy="parent", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection
     */
    protected $children;

    /**
     * @ORM\OneToMany(targetEntity="FolderTranslation", mappedBy="folder", orphanRemoval=true)
     * @var ArrayCollection
     */
    private $translatedFolders;

    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Document", inversedBy="folders")
     * @ORM\JoinTable(name="documents_folders")
     */
    protected $documents;

    /**
     * @return ArrayCollection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function addDocument(Document $document)
    {
        if (!$this->getDocuments()->contains($document)) {
            $this->documents->add($document);
        }

        return $this;
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function removeDocument(Document $document)
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
     * Create a new Folder.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->translatedFolders = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getTranslatedFolders()
    {
        return $this->translatedFolders;
    }

    /**
     * @param Translation $translation
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslatedFoldersByTranslation(Translation $translation)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('translation', $translation));

        return $this->translatedFolders->matching($criteria);
    }

    /**
     * @param mixed $translatedFolders
     * @return Folder
     */
    public function setTranslatedFolders($translatedFolders)
    {
        $this->translatedFolders = $translatedFolders;
        return $this;
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
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
     * @return \RZ\Roadiz\Core\Handlers\FolderHandler
     * @deprecated Use folder.handler service.
     */
    public function getHandler()
    {
        return new FolderHandler($this);
    }
}
