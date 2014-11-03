<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesDocuments.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\AbstractPositioned;
use RZ\Renzo\Core\AbstractEntities\PersistableInterface;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\NodeTypeField;

/**
 * Describes a complexe ManyToMany relation
 * between NodesSources, Documents and NodeTypeFields.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\NodesSourcesDocumentsRepository")
 * @Table(name="nodes_sources_documents")
 */
class NodesSourcesDocuments extends AbstractPositioned implements PersistableInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\NodesSources", inversedBy="documentsByFields")
     * @JoinColumn(name="ns_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Renzo\Core\Entities\NodesSources
     */
    private $nodeSource;

    /**
     * @return RZ\Renzo\Core\Entities\NodesSources
     */
    public function getNodeSource()
    {
        return $this->nodeSource;
    }

    public function setNodeSource($ns)
    {
        $this->nodeSource = $ns;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Document", inversedBy="nodesSourcesByFields")
     * @JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Renzo\Core\Entities\Document
     */
    private $document;

    /**
     * @return RZ\Renzo\Core\Entities\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    public function setDocument($doc)
    {
        $this->document = $doc;
    }


    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\NodeTypeField")
     * @JoinColumn(name="node_type_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Renzo\Core\Entities\NodeTypeField
     */
    private $field;

    /**
     * @return RZ\Renzo\Core\Entities\NodeTypeField
     */
    public function getField()
    {
        return $this->field;
    }

    public function setField($f)
    {
        $this->field = $f;
    }


    /**
     * Create a new relation between NodeSource, a Document and a NodeTypeField.
     *
     * @param mixed                                $nodeSource NodesSources and inherited types
     * @param RZ\Renzo\Core\Entities\Document      $document   Document to link
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field      NodeTypeField
     */
    public function __construct($nodeSource, Document $document, NodeTypeField $field)
    {
        $this->nodeSource = $nodeSource;
        $this->document = $document;
        $this->field = $field;
    }

    public function __clone()
    {
        $this->id = 0;
        $this->nodeSource = null;
    }
}
