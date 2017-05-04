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
 * @file NodeTypeFieldHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Handle operations with node-type fields entities.
 */
class NodeTypeFieldHandler
{

    private $nodeTypeField = null;
    /**
     * @return NodeTypeField
     */
    public function getNodeTypeField()
    {
        return $this->nodeTypeField;
    }
    /**
     * @param NodeTypeField $nodeTypeField
     *
     * @return $this
     */
    public function setNodeTypeField($nodeTypeField)
    {
        $this->nodeTypeField = $nodeTypeField;

        return $this;
    }

    /**
     * Create a new node-type-field handler with node-type-field to handle.
     *
     * @param NodeTypeField $field
     */
    public function __construct(NodeTypeField $field)
    {
        $this->nodeTypeField = $field;
    }

    /**
     * Clean position for current node siblings.
     *
     * @return int Return the next position after the **last** node
     */
    public function cleanPositions()
    {
        if ($this->nodeTypeField->getNodeType() !== null) {
            return $this->nodeTypeField->getNodeType()->getHandler()->cleanFieldsPositions();
        }

        return 1;
    }
}
