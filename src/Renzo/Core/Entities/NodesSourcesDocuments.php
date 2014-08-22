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
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\NodesSourcesDocumentsRepository")
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
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Document")
     * @JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Renzo\Core\Entities\Document
     */
    private $document;

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\NodeTypeField")
     * @JoinColumn(name="node_type_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Renzo\Core\Entities\NodeTypeField
     */
    private $field;

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
}