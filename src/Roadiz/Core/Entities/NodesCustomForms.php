<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file NodesCustomForms.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Doctrine\ORM\Mapping as ORM;

/**
 * Describes a complexe ManyToMany relation
 * between Nodes, CustomForms and NodeTypeFields.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesCustomFormsRepository")
 * @ORM\Table(name="nodes_custom_forms", indexes={
 *     @ORM\Index(name="position_nodescustomforms_idx", columns={"position"})
 * })
 */
class NodesCustomForms extends AbstractPositioned implements PersistableInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
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
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="customForms")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Roadiz\Core\Entities\Node
     */
    private $node;
    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getNode()
    {
        return $this->node;
    }

    public function setNode($node)
    {
        $this->node = $node;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm", inversedBy="nodes")
     * @ORM\JoinColumn(name="custom_form_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Roadiz\Core\Entities\CustomForm
     */
    private $customForm;
    /**
     * @return RZ\Roadiz\Core\Entities\CustomForm
     */
    public function getCustomForm()
    {
        return $this->customForm;
    }

    public function setCustomForm($customForm)
    {
        $this->customForm = $customForm;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodeTypeField")
     * @ORM\JoinColumn(name="node_type_field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Roadiz\Core\Entities\NodeTypeField
     */
    private $field;

    /**
     * @return RZ\Roadiz\Core\Entities\NodeTypeField
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
     * Create a new relation between a Node, a CustomForm and a NodeTypeField.
     *
     * @param RZ\Roadiz\Core\Entities\Node $node
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field NodeTypeField
     */
    public function __construct(Node $node, CustomForm $customForm, NodeTypeField $field)
    {
        $this->node = $node;
        $this->customForm = $customForm;
        $this->field = $field;
    }

    public function __clone()
    {
        $this->id = 0;
        $this->node = null;
    }
}
