<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\Models\DocumentInterface;

/**
 * Describes a complex ManyToMany relation
 * between Attribute and Documents.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Attribute\Repository\AttributeDocumentsRepository")
 * @ORM\Table(name="attributes_documents", indexes={
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"attribute_id", "position"})
 * })
 */
class AttributeDocuments extends AbstractPositioned
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Attribute", inversedBy="attributeDocuments", fetch="EAGER", cascade={"persist", "merge"})
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Attribute|null
     * @Serializer\Exclude()
     */
    protected ?Attribute $attribute = null;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Document", inversedBy="attributeDocuments", fetch="EAGER", cascade={"persist", "merge"})
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var DocumentInterface|null
     * @Serializer\Groups({"attribute"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Document")
     */
    protected ?DocumentInterface $document = null;

    /**
     * @param Attribute|null $attribute
     * @param DocumentInterface|null $document
     */
    public function __construct(?Attribute $attribute = null, ?DocumentInterface $document = null)
    {
        $this->document = $document;
        $this->attribute = $attribute;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->attribute = null;
        }
    }

    /**
     * Gets the value of document.
     *
     * @return DocumentInterface|null
     */
    public function getDocument(): ?DocumentInterface
    {
        return $this->document;
    }

    /**
     * Sets the value of document.
     *
     * @param DocumentInterface|null $document the document
     *
     * @return AttributeDocuments
     */
    public function setDocument(?DocumentInterface $document): AttributeDocuments
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return Attribute
     */
    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    /**
     * @param Attribute|null $attribute
     * @return AttributeDocuments
     */
    public function setAttribute(?Attribute $attribute): AttributeDocuments
    {
        $this->attribute = $attribute;
        return $this;
    }
}
