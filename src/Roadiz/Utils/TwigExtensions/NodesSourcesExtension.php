<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Extension that allow to gather nodes-source from hierarchy
 */
final class NodesSourcesExtension extends AbstractExtension
{
    protected AuthorizationChecker $securityAuthorizationChecker;
    protected HandlerFactory $handlerFactory;
    private bool $throwExceptions;
    private NodeSourceApi $nodeSourceApi;
    private NodeTypes $nodeTypesBag;

    /**
     * @param AuthorizationChecker $securityAuthorizationChecker
     * @param HandlerFactory $handlerFactory
     * @param NodeSourceApi $nodeSourceApi
     * @param NodeTypes $nodeTypesBag
     * @param bool $throwExceptions
     */
    public function __construct(
        AuthorizationChecker $securityAuthorizationChecker,
        HandlerFactory $handlerFactory,
        NodeSourceApi $nodeSourceApi,
        NodeTypes $nodeTypesBag,
        bool $throwExceptions = false
    ) {
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->throwExceptions = $throwExceptions;
        $this->nodeSourceApi = $nodeSourceApi;
        $this->handlerFactory = $handlerFactory;
        $this->nodeTypesBag = $nodeTypesBag;
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

    public function getTests()
    {
        $tests = [];

        /** @var NodeType $nodeType */
        foreach ($this->nodeTypesBag->all() as $nodeType) {
            $tests[] = new TwigTest($nodeType->getName(), function ($mixed) use ($nodeType) {
                return null !== $mixed && get_class($mixed) === $nodeType->getSourceEntityFullQualifiedClassName();
            });
            $tests[] = new TwigTest($nodeType->getSourceEntityClassName(), function ($mixed) use ($nodeType) {
                return null !== $mixed && get_class($mixed) === $nodeType->getSourceEntityFullQualifiedClassName();
            });
        }

        return $tests;
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
     * @return NodesSources|null
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
        /** @var NodesSourcesHandler $nodeSourceHandler */
        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getNext($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources|null
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
        /** @var NodesSourcesHandler $nodeSourceHandler */
        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getPrevious($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources|null
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
        /** @var NodesSourcesHandler $nodeSourceHandler */
        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getLastSibling($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources|null
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
        /** @var NodesSourcesHandler $nodeSourceHandler */
        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getFirstSibling($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @return NodesSources|null
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
     * @return array
     * @throws RuntimeError
     */
    public function getParents(NodesSources $ns = null, array $criteria = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new RuntimeError("Cannot get parents from a NULL node-source.");
            } else {
                return [];
            }
        }
        /** @var NodesSourcesHandler $nodeSourceHandler */
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
        /** @var NodesSourcesHandler $nodeSourceHandler */
        $nodeSourceHandler = $this->handlerFactory->getHandler($ns);
        return $nodeSourceHandler->getTags();
    }
}
