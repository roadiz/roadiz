<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Repositories\UrlAliasRepository;

final class NodeFactory
{
    private EntityManagerInterface $entityManager;

    private NodeNamePolicyInterface $nodeNamePolicy;

    /**
     * @param EntityManagerInterface $entityManager
     * @param NodeNamePolicyInterface $nodeNamePolicy
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        NodeNamePolicyInterface $nodeNamePolicy
    ) {
        $this->nodeNamePolicy = $nodeNamePolicy;
        $this->entityManager = $entityManager;
    }

    /**
     * @param string           $title
     * @param NodeType|null    $type
     * @param Translation|null $translation
     * @param Node|null        $node
     * @param Node|null        $parent
     *
     * @return Node
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(
        string $title,
        NodeType $type = null,
        Translation $translation = null,
        Node $node = null,
        Node $parent = null
    ): Node {
        /** @var NodeRepository $repository */
        $repository = $this->entityManager->getRepository(Node::class)
            ->setDisplayingAllNodesStatuses(true);

        if ($node === null && $type === null) {
            throw new \RuntimeException('Cannot create node from null NodeType and null Node.');
        }

        if ($translation === null) {
            $translation = $this->entityManager->getRepository(Translation::class)->findDefault();
        }

        if ($node === null) {
            $node = new Node($type);
        }

        $node->setTtl($node->getNodeType()->getDefaultTtl());
        if (null !== $parent) {
            $node->setParent($parent);
        }

        $sourceClass = $node->getNodeType()->getSourceEntityFullQualifiedClassName();
        /** @var NodesSources $source */
        $source = new $sourceClass($node, $translation);
        $source->injectObjectManager($this->entityManager, $this->entityManager->getClassMetadata($sourceClass));
        $source->setTitle($title);
        $source->setPublishedAt(new \DateTime());

        /*
         * Name node against policy
         */
        $nodeName = $this->nodeNamePolicy->getCanonicalNodeName($source);
        if (empty($nodeName)) {
            throw new \RuntimeException('Node name is empty.');
        }
        if (true === $repository->exists($nodeName)) {
            $nodeName = $this->nodeNamePolicy->getSafeNodeName($source);
        }
        if (mb_strlen($nodeName) > 250) {
            throw new \InvalidArgumentException(sprintf('Node name "%s" is too long.', $nodeName));
        }
        $node->setNodeName($nodeName);

        $this->entityManager->persist($source);
        $this->entityManager->persist($node);

        return $node;
    }

    /**
     * @param string           $urlAlias
     * @param string           $title
     * @param NodeType|null    $type
     * @param Translation|null $translation
     * @param Node|null        $node
     * @param Node|null        $parent
     *
     * @return Node
     * @throws \Doctrine\ORM\ORMException
     */
    public function createWithUrlAlias(
        string $urlAlias,
        string $title,
        NodeType $type = null,
        Translation $translation = null,
        Node $node = null,
        Node $parent = null
    ): Node {
        $node = $this->create($title, $type, $translation, $node, $parent);
        /** @var UrlAliasRepository $repository */
        $repository = $this->entityManager->getRepository(UrlAlias::class);
        if (false === $repository->exists($urlAlias)) {
            $alias = new UrlAlias($node->getNodeSources()->first());
            $alias->setAlias($urlAlias);
            $this->entityManager->persist($alias);
        }

        return $node;
    }
}
