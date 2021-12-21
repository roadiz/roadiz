<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;

/**
 * Describes a complex ManyToMany relation
 * between NodesSources, Documents and NodeTypeFields.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesSourcesDocumentsRepository")
 * @ORM\Table(name="nodes_sources_documents", indexes={
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"ns_id", "node_type_field_id"}),
 *     @ORM\Index(columns={"ns_id", "node_type_field_id", "position"})
 * })
 */
class NodesSourcesDocuments extends AbstractPositioned
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodesSources", inversedBy="documentsByFields", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="ns_id", referencedColumnName="id", onDelete="CASCADE")
     * @var NodesSources|null
     */
    protected $nodeSource;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Document", inversedBy="nodesSourcesByFields", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Document|null
     */
    protected $document;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodeTypeField")
     * @ORM\JoinColumn(name="node_type_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var NodeTypeField|null
     */
    protected $field;

    /**
     * Create a new relation between NodeSource, a Document and a NodeTypeField.
     *
     * @param NodesSources  $nodeSource NodesSources and inherited types
     * @param Document      $document   Document to link
     * @param NodeTypeField $field      NodeTypeField
     */
    public function __construct(NodesSources $nodeSource, Document $document, NodeTypeField $field)
    {
        $this->nodeSource = $nodeSource;
        $this->document = $document;
        $this->field = $field;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->nodeSource = null;
        }
    }

    /**
     * Gets the value of nodeSource.
     *
     * @return NodesSources|null
     */
    public function getNodeSource(): ?NodesSources
    {
        return $this->nodeSource;
    }

    /**
     * Sets the value of nodeSource.
     *
     * @param NodesSources|null $nodeSource the node source
     *
     * @return self
     */
    public function setNodeSource(?NodesSources $nodeSource): NodesSourcesDocuments
    {
        $this->nodeSource = $nodeSource;

        return $this;
    }

    /**
     * Gets the value of document.
     *
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * Sets the value of document.
     *
     * @param Document|null $document the document
     *
     * @return self
     */
    public function setDocument(?Document $document): NodesSourcesDocuments
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Gets the value of field.
     *
     * @return NodeTypeField
     */
    public function getField(): NodeTypeField
    {
        return $this->field;
    }

    /**
     * Sets the value of field.
     *
     * @param NodeTypeField|null $field the field
     *
     * @return self
     */
    public function setField(?NodeTypeField $field): NodesSourcesDocuments
    {
        $this->field = $field;

        return $this;
    }
}
