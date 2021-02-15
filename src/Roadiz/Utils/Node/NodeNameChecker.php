<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\ORM\EntityManagerInterface;
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
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getCanonicalNodeName(NodesSources $nodeSource): string
    {
        if ($nodeSource->getTitle() !== '') {
            if ($nodeSource->getNode()->getNodeType()->isReachable()) {
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
            uniqid()
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
        $urlAliasRepo = $this->entityManager->getRepository(UrlAlias::class);
        /** @var NodeRepository $nodeRepo */
        $nodeRepo = $this->entityManager
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true);

        if (false === (boolean) $urlAliasRepo->exists($nodeName) &&
            false === (boolean) $nodeRepo->exists($nodeName)) {
            return false;
        }
        return true;
    }
}
