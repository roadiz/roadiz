<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Utils\StringHandler;

/**
 * Class NodeNameChecker
 * @package RZ\Roadiz\Utils\Node
 */
class NodeNameChecker
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * NodeNameChecker constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Test if current node name is suffixed with a 13 chars Unique ID (uniqid()).
     *
     * @param string $canonicalNodeName Node name without uniqid after.
     * @param string $nodeName Node name to test
     * @return bool
     */
    public function isNodeNameWithUniqId(string $canonicalNodeName, string $nodeName)
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
     * @return bool
     */
    public function isNodeNameAlreadyUsed(string $nodeName): bool
    {
        $nodeName = StringHandler::slugify($nodeName);

        if (false === (boolean) $this->entityManager
                ->getRepository(UrlAlias::class)
                ->exists($nodeName) &&
            false === (boolean) $this->entityManager
                ->getRepository(Node::class)
                ->setDisplayingNotPublishedNodes(true)
                ->exists($nodeName)) {
            return false;
        }
        return true;
    }
}
