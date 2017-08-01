<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodesCustomForms.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\AbstractEntities\PositionedInterface;
use RZ\Roadiz\Core\AbstractEntities\PositionedTrait;

/**
 * Describes a complexe ManyToMany relation
 * between Nodes, CustomForms and NodeTypeFields.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesCustomFormsRepository")
 * @ORM\Table(name="nodes_custom_forms", indexes={
 *     @ORM\Index(columns={"position"})
 * })
 */
class NodesCustomForms extends AbstractEntity implements PositionedInterface
{
    use PositionedTrait;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="customForms", fetch="EAGER")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Node
     */
    protected $node;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm", inversedBy="nodes", fetch="EAGER")
     * @ORM\JoinColumn(name="custom_form_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomForm
     */
    protected $customForm;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodeTypeField")
     * @ORM\JoinColumn(name="node_type_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var NodeTypeField
     */
    protected $field;

    /**
     * Create a new relation between a Node, a CustomForm and a NodeTypeField.
     *
     * @param \RZ\Roadiz\Core\Entities\Node $node
     * @param \RZ\Roadiz\Core\Entities\CustomForm $customForm
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $field NodeTypeField
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
     * @return Node
     */
    public function getNode()
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
    public function setNode(Node $node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Gets the value of customForm.
     *
     * @return CustomForm
     */
    public function getCustomForm()
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
    public function setCustomForm(CustomForm $customForm)
    {
        $this->customForm = $customForm;

        return $this;
    }

    /**
     * Gets the value of field.
     *
     * @return NodeTypeField
     */
    public function getField()
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
    public function setField(NodeTypeField $field)
    {
        $this->field = $field;

        return $this;
    }
}
