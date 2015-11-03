<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodeXlsxSerializer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Serializers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;

/**
 * XLSX Serialization handler for Node.
 */
class NodeXlsxSerializer extends AbstractXlsxSerializer
{
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    /**
     * Create a simple associative array with a Node.
     *
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return array
     */
    public function toArray($nodes)
    {
        $array = [];
        $nsSerializer = new NodeSourceXlsxSerializer();

        foreach ($nodes as $node) {
            $data = [];

            $data['node_name'] = $node->getNodeName();
            $data['node_type'] = $node->getNodeType()->getName();
            $data['home'] = $node->isHome();
            $data['visible'] = $node->isVisible();
            $data['status'] = $node->getStatus();
            $data['locked'] = $node->isLocked();
            $data['priority'] = $node->getPriority();
            $data['hiding_children'] = $node->isHidingChildren();
            $data['archived'] = $node->isArchived();
            $data['sterile'] = $node->isSterile();
            $data['children_order'] = $node->getChildrenOrder();
            $data['children_order_direction'] = $node->getChildrenOrderDirection();

            $data['children'] = [];
            $data['nodes_sources'] = [];
            $data['tags'] = [];

            foreach ($node->getNodeSources() as $source) {
                $data['nodes_sources'][] = $nsSerializer->toArray($source);
            }

            foreach ($node->getTags() as $tag) {
                $data['tags'][] = $tag->getTagName();
            }
            /*
             * Recursivity !! Be careful
             */
            foreach ($node->getChildren() as $child) {
                $data['children'][] = $this->toArray([$child])[0];
            }
            $array[] = $data;
        }
        return $array;
    }

    /**
     *
     * @param string $string
     *
     * @return null
     */
    public function deserialize($string)
    {
        return null;
    }
}
