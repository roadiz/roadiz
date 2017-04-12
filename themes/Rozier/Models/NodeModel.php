<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file NodeModel.php
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class NodeModel.
 *
 * @package Themes\Rozier\Models
 */
class NodeModel implements ModelInterface
{
    public static $thumbnailArray;
    /**
     * @var Node
     */
    private $node;
    /**
     * @var Container
     */
    private $container;

    /**
     * NodeModel constructor.
     * @param Node $node
     * @param Container $container
     */
    public function __construct(Node $node, Container $container)
    {
        $this->node = $node;
        $this->container = $container;
    }

    public function toArray()
    {
        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = $this->container->offsetGet('urlGenerator');

        $result = [
            'id' => $this->node->getId(),
            'title' => $this->node->getNodeSources()->first()->getTitle(),
            'nodeName' => $this->node->getNodeName(),
            'isPusblished' => $this->node->isPublished(),
            'nodesEditPage' => $urlGenerator->generate('nodesEditPage', [
                'nodeId' => $this->node->getId()
            ]),
            'nodeType' => [
                'color' => $this->node->getNodeType()->getColor()
            ]
        ];

        $parent = $this->node->getParent();

        if ($parent) {
            $result['parent'] = [
                'title' => $parent->getNodeSources()->first()->getTitle()
            ];

            if ($parent->getParent()) {
                $subparent = $parent->getParent();

                $result['subparent'] = [
                    'title' => $subparent->getNodeSources()->first()->getTitle()
                ];
            }
        }

        return $result;
    }
}
