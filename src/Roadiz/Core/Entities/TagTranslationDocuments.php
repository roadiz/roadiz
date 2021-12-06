<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\Models\DocumentInterface;

/**
 * Describes a complex ManyToMany relation
 * between TagTranslation and Documents.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\TagTranslationDocumentsRepository")
 * @ORM\Table(name="tags_translations_documents", indexes={
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"tag_translation_id", "position"}, name="tagtranslation_position")
 * })
 */
class TagTranslationDocuments extends AbstractPositioned
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\TagTranslation", inversedBy="tagTranslationDocuments", fetch="EAGER", cascade={"persist", "merge"})
     * @ORM\JoinColumn(name="tag_translation_id", referencedColumnName="id", onDelete="CASCADE")
     * @var TagTranslation|null
     * @Serializer\Exclude()
     */
    protected ?TagTranslation $tagTranslation = null;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Document", inversedBy="tagTranslations", fetch="EAGER", cascade={"persist", "merge"})
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var DocumentInterface|null
     * @Serializer\Groups({"tag"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Document")
     */
    protected ?DocumentInterface $document = null;

    /**
     * Create a new relation between NodeSource, a Document and a NodeTypeField.
     *
     * @param TagTranslation|null $tagTranslation
     * @param DocumentInterface|null $document
     */
    public function __construct(TagTranslation $tagTranslation = null, ?DocumentInterface $document = null)
    {
        $this->document = $document;
        $this->tagTranslation = $tagTranslation;
    }

    /**
     *
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->tagTranslation = null;
        }
    }

    /**
     * Gets the value of document.
     *
     * @return DocumentInterface
     */
    public function getDocument(): ?DocumentInterface
    {
        return $this->document;
    }

    /**
     * Sets the value of document.
     *
     * @param DocumentInterface|null $document the document
     * @return self
     */
    public function setDocument(?DocumentInterface $document): TagTranslationDocuments
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return TagTranslation
     */
    public function getTagTranslation(): ?TagTranslation
    {
        return $this->tagTranslation;
    }

    /**
     * @param TagTranslation|null $tagTranslation
     * @return TagTranslationDocuments
     */
    public function setTagTranslation(?TagTranslation $tagTranslation): TagTranslationDocuments
    {
        $this->tagTranslation = $tagTranslation;
        return $this;
    }
}
