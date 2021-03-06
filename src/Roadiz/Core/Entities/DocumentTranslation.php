<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\DocumentTranslationRepository")
 * @ORM\Table(name="documents_translations", uniqueConstraints={@ORM\UniqueConstraint(columns={"document_id", "translation_id"})})
 * @Gedmo\Loggable(logEntryClass="RZ\Roadiz\Core\Entities\UserLogEntry")
 */
class DocumentTranslation extends AbstractEntity implements Loggable
{
    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     * @Serializer\Groups({"document", "nodes_sources", "tag", "attribute"})
     * @Gedmo\Versioned
     */
    protected $name = null;
    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    /**
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name): DocumentTranslation
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"document", "nodes_sources", "tag", "attribute"})
     * @Gedmo\Versioned
     */
    protected $description;

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description): DocumentTranslation
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"document", "nodes_sources", "tag", "attribute"})
     * @Gedmo\Versioned
     * @var string|null
     */
    private $copyright;

    /**
     * @return string|null
     */
    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    /**
     * @param string|null $copyright
     *
     * @return $this
     */
    public function setCopyright(?string $copyright): DocumentTranslation
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Translation", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     * @Serializer\Groups({"document", "nodes_sources", "tag", "attribute"})
     * @var Translation|null
     */
    protected $translation;

    /**
     * @return Translation
     */
    public function getTranslation(): Translation
    {
        return $this->translation;
    }

    /**
     * @param Translation $translation
     * @return $this
     */
    public function setTranslation(Translation $translation): DocumentTranslation
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Document|null
     * @Serializer\Exclude
     */
    protected $document;

    /**
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
        return $this;
    }
}
