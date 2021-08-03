<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Repositories\UrlAliasRepository;
use RZ\Roadiz\Utils\StringHandler;

/**
 * @package RZ\Roadiz\Utils\Node
 */
class NodeNameChecker implements NodeNamePolicyInterface
{
    protected bool $useTypedSuffix;
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param bool $useTypedSuffix
     */
    public function __construct(ManagerRegistry $managerRegistry, bool $useTypedSuffix = false)
    {
        $this->useTypedSuffix = $useTypedSuffix;
        $this->managerRegistry = $managerRegistry;
    }

    public function getCanonicalNodeName(NodesSources $nodeSource): string
    {
        if ($nodeSource->getTitle() !== '') {
            if ($nodeSource->isReachable() || !$this->useTypedSuffix) {
                return StringHandler::slugify($nodeSource->getTitle());
            }
            return sprintf(
                '%s-%s',
                StringHandler::slugify($nodeSource->getTitle()),
                StringHandler::slugify($nodeSource->getNodeTypeName()),
            );
        }
        return sprintf(
            '%s-%s',
            StringHandler::slugify($nodeSource->getNodeTypeName()),
            null !== $nodeSource->getNode() ? $nodeSource->getNode()->getId() : $nodeSource->getId()
        );
    }

    public function getSafeNodeName(NodesSources $nodeSource): string
    {
        return sprintf(
            '%s-%s',
            $this->getCanonicalNodeName($nodeSource),
            uniqid()
        );
    }

    public function getDatestampedNodeName(NodesSources $nodeSource): string
    {
        return sprintf(
            '%s-%s',
            $this->getCanonicalNodeName($nodeSource),
            $nodeSource->getPublishedAt()->format('Y-m-d')
        );
    }

    /**
     * Test if current node name is suffixed with a 13 chars Unique ID (uniqid()).
     *
     * @param string $canonicalNodeName Node name without uniqid after.
     * @param string $nodeName Node name to test
     * @return bool
     */
    public function isNodeNameWithUniqId(string $canonicalNodeName, string $nodeName): bool
    {
        $pattern = '#^' . preg_quote($canonicalNodeName) . '\-[0-9a-z]{13}$#';
        $returnState = preg_match_all($pattern, $nodeName);

        if (1 === $returnState) {
            return true;
        }

        return false;
    }

    /**
     * @param string $nodeName
     *
     * @return bool
     */
    public function isNodeNameValid(string $nodeName): bool
    {
        if (preg_match('#^[a-zA-Z0-9\-]+$#', $nodeName) === 1) {
            return true;
        }
        return false;
    }

    /**
     * Test if node’s name is already used as a name or an url-alias.
     *
     * @param string $nodeName
     *
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNodeNameAlreadyUsed(string $nodeName): bool
    {
        $nodeName = StringHandler::slugify($nodeName);
        /** @var UrlAliasRepository $urlAliasRepo */
        $urlAliasRepo = $this->managerRegistry->getRepository(UrlAlias::class);
        /** @var NodeRepository $nodeRepo */
        $nodeRepo = $this->managerRegistry
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true);

        if (false === (boolean) $urlAliasRepo->exists($nodeName) &&
            false === (boolean) $nodeRepo->exists($nodeName)) {
            return false;
        }
        return true;
    }
}
