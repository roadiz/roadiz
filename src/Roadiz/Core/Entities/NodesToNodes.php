<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;

/**
 * Describes a complex ManyToMany relation
 * between two Nodes and NodeTypeFields.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesToNodesRepository")
 * @ORM\Table(name="nodes_to_nodes", indexes={
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"node_a_id", "node_type_field_id"}, name="node_a_field"),
 *     @ORM\Index(columns={"node_a_id", "node_type_field_id", "position"}, name="node_a_field_position"),
 *     @ORM\Index(columns={"node_b_id", "node_type_field_id"}, name="node_b_field"),
 *     @ORM\Index(columns={"node_b_id", "node_type_field_id", "position"}, name="node_b_field_position")
 * })
 */
class NodesToNodes extends AbstractPositioned
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="bNodes", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="node_a_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Node|null
     */
    protected ?Node $nodeA;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="aNodes", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="node_b_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Node|null
     */
    protected ?Node $nodeB;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodeTypeField")
     * @ORM\JoinColumn(name="node_type_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var NodeTypeField|null
     */
    protected ?NodeTypeField $field;

    /**
     * Create a new relation between two Nodes and a NodeTypeField.
     *
     * @param Node          $nodeA
     * @param Node          $nodeB
     * @param NodeTypeField $field NodeTypeField
     */
    public function __construct(Node $nodeA, Node $nodeB, NodeTypeField $field)
    {
        $this->nodeA = $nodeA;
        $this->nodeB = $nodeB;
        $this->field = $field;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->nodeA = null;
        }
    }

    /**
     * Gets the value of nodeA.
     *
     * @return Node|null
     */
    public function getNodeA(): ?Node
    {
        return $this->nodeA;
    }

    /**
     * Sets the value of nodeA.
     *
     * @param Node|null $nodeA the node
     *
     * @return self
     */
    public function setNodeA(?Node $nodeA): NodesToNodes
    {
        $this->nodeA = $nodeA;

        return $this;
    }

    /**
     * Gets the value of nodeB.
     *
     * @return Node|null
     */
    public function getNodeB(): ?Node
    {
        return $this->nodeB;
    }

    /**
     * Sets the value of nodeB.
     *
     * @param Node|null $nodeB the node
     *
     * @return self
     */
    public function setNodeB(?Node $nodeB): NodesToNodes
    {
        $this->nodeB = $nodeB;

        return $this;
    }

    /**
     * Gets the value of field.
     *
     * @return NodeTypeField|null
     */
    public function getField(): ?NodeTypeField
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
    public function setField(?NodeTypeField $field): NodesToNodes
    {
        $this->field = $field;

        return $this;
    }
}
