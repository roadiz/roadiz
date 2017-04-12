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
 * @file NodeTypeModel.php
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodeType;

class NodeTypeModel
{
    public static $thumbnailArray;
    /**
     * @var NodeType
     */
    private $nodeType;
    /**
     * @var Container
     */
    private $container;

    /**
     * NodeModel constructor.
     * @param NodeType $nodeType
     * @param Container $container
     */
    public function __construct(NodeType $nodeType, Container $container)
    {
        $this->nodeType = $nodeType;
        $this->container = $container;
    }

    public function toArray()
    {
        $result = [
            'id' => $this->nodeType->getId(),
            'nodeName' => $this->nodeType->getName(),
            'name' => $this->nodeType->getDisplayName(),
            'color' => $this->nodeType->getColor(),
        ];

        return $result;
    }
}
