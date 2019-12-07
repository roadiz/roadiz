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

use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension that allow to gather nodes-source from hierarchy
 */
class NodesSourcesExtension extends AbstractExtension
{
    protected $preview;
    protected $securityAuthorizationChecker;
    /**
     * @var HandlerFactory
     */
    protected $handlerFactory;
    /**
     * @var bool
     */
    private $throwExceptions;
    /**
     * @var NodeSourceApi
     */
    private $nodeSourceApi;

    /**
     * NodesSourcesExtension constructor.
     *
     * @param AuthorizationChecker $securityAuthorizationChecker
     * @param HandlerFactory       $handlerFactory
     * @param NodeSourceApi        $nodeSourceApi
     * @param bool                 $preview
     * @param bool                 $throwExceptions
     */
    public function __construct(
        AuthorizationChecker $securityAuthorizationChecker,
        HandlerFactory $handlerFactory,
        NodeSourceApi $nodeSourceApi,
        $preview = false,
        $throwExceptions = false
    ) {
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->preview = $preview;
        $this->throwExceptions = $throwExceptions;
        $this->nodeSourceApi = $nodeSourceApi;
        $this->handlerFactory = $handlerFactory;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('children', [$this, 'getChildren']),
            new TwigFilter('next', [$this, 'getNext']),
            new TwigFilter('previous', [$this, 'getPrevious']),
            new TwigFilter('lastSibling', [$this, 'getLastSibling']),
            new TwigFilter('firstSibling', [$this, 'getFirstSibling']),
            new TwigFilter('parent', [$this, 'getParent']),
            new TwigFilter('parents', [$this, 'getParents']),
            new TwigFilter('tags', [$this, 'getTags']),
        ];
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return array
     * @throws RuntimeError
     */
    public function getChildren(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get children from a NULL node-source.");
            } else {
                return [];
            }
        }
        $defaultCrit = [
            'node.parent' => $ns->getNode(),
            'translation' => $ns->getTranslation(),
        ];

        if (null !== $order) {
            $defaultOrder = $order;
        } else {
            $defaultOrder = [
                'node.position' => 'ASC',
            ];
        }

        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        return $this->nodeSourceApi->getBy($defaultCrit, $defaultOrder);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws RuntimeError
     */
    public function getNext(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get next sibling from a NULL node-source.");
            } else {
                return null;
            }
        }
        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getNext($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws RuntimeError
     */
    public function getPrevious(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get previous sibling from a NULL node-source.");
            } else {
                return null;
            }
        }

        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getPrevious($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws RuntimeError
     */
    public function getLastSibling(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get last sibling from a NULL node-source.");
            } else {
                return null;
            }
        }

        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getLastSibling($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws RuntimeError
     */
    public function getFirstSibling(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get first sibling from a NULL node-source.");
            } else {
                return null;
            }
        }

        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getFirstSibling($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @return NodesSources
     * @throws RuntimeError
     */
    public function getParent(NodesSources $ns = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get parent from a NULL node-source.");
            } else {
                return null;
            }
        }

        return $ns->getParent();
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param null $preview
     * @return array
     * @throws RuntimeError
     */
    public function getParents(NodesSources $ns = null, array $criteria = null, $preview = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get parents from a NULL node-source.");
            } else {
                return [];
            }
        }

        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getParents($criteria);
    }

    /**
     * @param NodesSources|null $ns
     * @return array
     * @throws RuntimeError
     */
    public function getTags(NodesSources $ns = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get tags from a NULL node-source.");
            } else {
                return [];
            }
        }

        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getTags();
    }
}
