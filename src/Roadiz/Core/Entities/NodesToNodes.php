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
 * @file NodesToNodes.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Doctrine\ORM\Mapping as ORM;

/**
 * Describes a complexe ManyToMany relation
 * between two Nodes and NodeTypeFields.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesToNodesRepository")
 * @ORM\Table(name="nodes_to_nodes", indexes={
 *     @ORM\Index(name="position_nodestonodes_idx", columns={"position"})
 * })
 */
class NodesToNodes extends AbstractPositioned implements PersistableInterface
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
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="bNodes")
     * @ORM\JoinColumn(name="node_a_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Roadiz\Core\Entities\Node
     */
    private $nodeA;
    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getNodeA()
    {
        return $this->nodeA;
    }

    public function setNodeA($nodeA)
    {
        $this->nodeA = $nodeA;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="aNodes")
     * @ORM\JoinColumn(name="node_b_id", referencedColumnName="id", onDelete="CASCADE")
     * @var RZ\Roadiz\Core\Entities\Node
     */
    private $nodeB;
    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getNodeB()
    {
        return $this->nodeB;
    }

    public function setNodeB($nodeB)
    {
        $this->nodeB = $nodeB;
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
     * Create a new relation between two Nodes and a NodeTypeField.
     *
     * @param RZ\Roadiz\Core\Entities\Node $nodeA
     * @param RZ\Roadiz\Core\Entities\Node $nodeB
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field NodeTypeField
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
