<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;

/**
 * Describes a complex ManyToMany relation
 * between Nodes, CustomForms and NodeTypeFields.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesCustomFormsRepository")
 * @ORM\Table(name="nodes_custom_forms", indexes={
 *     @ORM\Index(columns={"position"}),
 *     @ORM\Index(columns={"node_id", "position"}, name="customform_node_position"),
 *     @ORM\Index(columns={"node_id", "node_type_field_id", "position"}, name="customform_node_field_position")
 * })
 */
class NodesCustomForms extends AbstractPositioned
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="customForms", fetch="EAGER")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Node|null
     */
    protected $node;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm", inversedBy="nodes", fetch="EAGER")
     * @ORM\JoinColumn(name="custom_form_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomForm|null
     */
    protected $customForm;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodeTypeField")
     * @ORM\JoinColumn(name="node_type_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var NodeTypeField|null
     */
    protected $field;

    /**
     * Create a new relation between a Node, a CustomForm and a NodeTypeField.
     *
     * @param Node          $node
     * @param CustomForm    $customForm
     * @param NodeTypeField $field NodeTypeField
     */
    public function __construct(Node $node, CustomForm $customForm, NodeTypeField $field)
    {
        $this->node = $node;
        $this->customForm = $customForm;
        $this->field = $field;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->node = null;
        }
    }

    /**
     * Gets the value of node.
     *
     * @return Node|null
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * Sets the value of node.
     *
     * @param Node $node the node
     *
     * @return self
     */
    public function setNode(Node $node): NodesCustomForms
    {
        $this->node = $node;
        return $this;
    }

    /**
     * Gets the value of customForm.
     *
     * @return CustomForm
     */
    public function getCustomForm(): CustomForm
    {
        return $this->customForm;
    }

    /**
     * Sets the value of customForm.
     *
     * @param CustomForm $customForm the custom form
     *
     * @return self
     */
    public function setCustomForm(CustomForm $customForm): NodesCustomForms
    {
        $this->customForm = $customForm;

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
     * @param NodeTypeField $field the field
     *
     * @return self
     */
    public function setField(NodeTypeField $field): NodesCustomForms
    {
        $this->field = $field;

        return $this;
    }
}
