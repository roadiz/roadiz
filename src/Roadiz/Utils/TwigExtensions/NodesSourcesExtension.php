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
 * @file NodesSourcesExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Extension that allow to gather nodes-source from hierarchy
 */
class NodesSourcesExtension extends \Twig_Extension
{
    protected $preview;
    protected $securityAuthorizationChecker;

    /**
     * @param AuthorizationChecker $securityAuthorizationChecker
     * @param boolean              $preview
     */
    public function __construct(AuthorizationChecker $securityAuthorizationChecker, $preview = false)
    {
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->preview = $preview;
    }

    public function getName()
    {
        return 'nodesSourcesExtension';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('children', [$this, 'getChildren']),
            new \Twig_SimpleFilter('next', [$this, 'getNext']),
            new \Twig_SimpleFilter('previous', [$this, 'getPrevious']),
            new \Twig_SimpleFilter('lastSibling', [$this, 'getLastSibling']),
            new \Twig_SimpleFilter('firstSibling', [$this, 'getFirstSibling']),
            new \Twig_SimpleFilter('parent', [$this, 'getParent']),
            new \Twig_SimpleFilter('parents', [$this, 'getParents']),
            new \Twig_SimpleFilter('tags', [$this, 'getTags']),
        ];
    }

    public function getChildren(NodesSources $ns, array $criteria = null, array $order = null)
    {
        return $ns->getHandler()->getChildren($criteria, $order, $this->securityAuthorizationChecker, $this->preview);
    }

    public function getNext(NodesSources $ns, array $criteria = null, array $order = null)
    {
        return $ns->getHandler()->getNext($criteria, $order, $this->securityAuthorizationChecker, $this->preview);
    }

    public function getPrevious(NodesSources $ns, array $criteria = null, array $order = null)
    {
        return $ns->getHandler()->getPrevious($criteria, $order, $this->securityAuthorizationChecker, $this->preview);
    }

    public function getLastSibling(NodesSources $ns, array $criteria = null, array $order = null)
    {
        return $ns->getHandler()->getLastSibling($criteria, $order, $this->securityAuthorizationChecker, $this->preview);
    }

    public function getFirstSibling(NodesSources $ns, array $criteria = null, array $order = null)
    {
        return $ns->getHandler()->getFirstSibling($criteria, $order, $this->securityAuthorizationChecker, $this->preview);
    }

    public function getParent(NodesSources $ns)
    {
        return $ns->getHandler()->getParent();
    }

    public function getParents(NodesSources $ns, array $criteria = null, $preview = null)
    {
        $preview = $preview !== null ? $preview : $this->preview;
        return $ns->getHandler()->getParents($criteria, $this->securityAuthorizationChecker, $preview);
    }

    public function getTags(NodesSources $ns)
    {
        return $ns->getHandler()->getTags();
    }
}
