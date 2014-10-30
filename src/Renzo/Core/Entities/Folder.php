<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file Folder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimed;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Viewers\DocumentViewer;
use RZ\Renzo\Core\Handlers\DocumentHandler;

/**
 * Folders entity represent a directory on server with datetime and naming.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="folders")
 */
class Folder extends AbstractDateTimed
{
    /**
     * @Column(type="string", unique=true, nullable=false)
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Folder", inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent = null;

    /**
     * @return RZ\Renzo\Core\Entities\Folder
     */
    public function getParent()
    {
        return $this->parent;
    }
    /**
     * @param RZ\Renzo\Core\Entities\Folder $parent
     */
    public function setParent(Folder $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\Folder", mappedBy="parent")
     */
    protected $children;

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }
    /**
     * @param Folder $child
     */
    public function addChild(Folder $child)
    {
        if (!$this->getChildren()->contains($child)) {
            $this->children->add($child);
        }

        return $this;
    }


    /**
     * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Document", inversedBy="folders", fetch="EXTRA_LAZY")
     * @JoinTable(name="documents_folders")
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
     */
    public function addDocument(Document $document)
    {
        if (!$this->getDocuments()->contains($document)) {
            $this->documents->add($document);
        }

        return $this;
    }

    /**
     * Create a new Folder.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }
}
