<?php
/**
 * Copyright (c) 2016.
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
 * @file NodeRouteHelper.php
 * @author ambroisemaupate
 *
 */

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class NodeRouteHelper
{
    /**
     * @var Node
     */
    private $node;
    /**
     * @var Theme
     */
    private $theme;
    /**
     * @var bool
     */
    private $preview;

    /**
     * NodeRouteHelper constructor.
     * @param Node $node
     * @param Theme $theme
     * @param bool $preview
     */
    public function __construct(Node $node, Theme $theme, $preview = false)
    {
        $this->node = $node;
        $this->theme = $theme;
        $this->preview = $preview;
    }

    /**
     * Get controller class path for a given node.
     *
     * @return string
     */
    public function getController()
    {
        $refl = new \ReflectionClass($this->theme->getClassName());
        $namespace = $refl->getNamespaceName() . '\\Controllers';

        return $namespace . '\\' .
        StringHandler::classify($this->node->getNodeType()->getName()) .
        'Controller';
    }

    /**
     * Return FALSE or TRUE if node is viewable.
     *
     * @return boolean
     */
    public function isViewable()
    {
        /*
         * For archived and deleted nodes
         */
        if ($this->node->getStatus() > Node::PUBLISHED) {
            /*
             * Not allowed to see deleted and archived nodes
             * even for Admins
             */
            return false;
        }

        /*
         * For unpublished nodes
         */
        if ($this->node->getStatus() < Node::PUBLISHED) {
            if (true === $this->preview) {
                return true;
            }
            /*
             * Not allowed to see unpublished nodes
             */
            return false;
        }

        /*
         * Everyone can view published nodes.
         */
        return true;
    }
}
