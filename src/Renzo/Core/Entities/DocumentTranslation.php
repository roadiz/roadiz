<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file DocumentTranslation.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Viewers\DocumentViewer;
use RZ\Renzo\Core\Handlers\DocumentHandler;

/**
 * DocumentTranslation.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="documents_translations", uniqueConstraints={@UniqueConstraint(columns={"document_id", "translation_id"})})
 */
class DocumentTranslation extends AbstractEntity
{
    /**
     * @Column(type="string", nullable=true)
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
     * @Column(type="text", nullable=true)
     */
    private $description;
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @Column(type="text", nullable=true)
     */
    private $copyright;
    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }
    /**
     * @param string $copyright
     *
     * @return $this
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Translation", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $translation;

    /**
     * @return RZ\Renzo\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     */
    public function setTranslation(Translation $translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="Document", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $document;

    /**
     * @return RZ\Renzo\Core\Entities\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Document $document
     */
    public function setDocument($document)
    {
        $this->document = $document;

        return $this;
    }

}
