<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesToNodes.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\AbstractPositioned;
use RZ\Renzo\Core\AbstractEntities\PersistableInterface;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeTypeField;

/**
 * Describes a complexe ManyToMany relation
 * between two Nodes and NodeTypeFields.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\NodesToNodesRepository")
 * @Table(name="nodes_to_nodes", indexes={
 *     @index(name="position_nodestonodes_idx", columns={"position"})
 * })
 */
class NodesToNodes extends AbstractPositioned implements PersistableInterface
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
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Node", inversedBy="bNodes")
     * @JoinColumn(name="node_a_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Renzo\Core\Entities\Node
     */
    private $nodeA;

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Node", inversedBy="aNodes")
     * @JoinColumn(name="node_b_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Renzo\Core\Entities\Node
     */
    private $nodeB;

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
     * Create a new relation between two Nodes and a NodeTypeField.
     *
     * @param RZ\Renzo\Core\Entities\Node $nodeA
     * @param RZ\Renzo\Core\Entities\Node $nodeB
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field NodeTypeField
     */
    public function __construct(Node $nodeA, Node $nodeB, NodeTypeField $field)
    {
        $this->nodeA = $nodeA;
        $this->nodeB = $nodeB;
        $this->field = $field;
    }

    public function __clone()
    {
        $this->id = 0;
        $this->nodeA = null;
    }
}
