<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @package RZ\Roadiz\Utils\Node
 */
class UniqueNodeGenerator
{
    protected EntityManagerInterface $entityManager;

    protected NodeNamePolicyInterface $nodeNamePolicy;

    /**
     * @param EntityManagerInterface $entityManager
     * @param NodeNamePolicyInterface $nodeNamePolicy
     */
    public function __construct(EntityManagerInterface $entityManager, NodeNamePolicyInterface $nodeNamePolicy)
    {
        $this->entityManager = $entityManager;
        $this->nodeNamePolicy = $nodeNamePolicy;
    }

    /**
     * Generate a node with a unique name.
     *
     * This method flush entity-manager.
     *
     * @param NodeType $nodeType
     * @param Translation $translation
     * @param Node|null $parent
     * @param Tag|null $tag
     * @param bool $pushToTop
     *
     * @return NodesSources
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generate(
        NodeType $nodeType,
        Translation $translation,
        Node $parent = null,
        Tag $tag = null,
        bool $pushToTop = false
    ) {
        $name = $nodeType->getDisplayName() . " " . uniqid();
        $node = new Node($nodeType);
        $node->setTtl($node->getNodeType()->getDefaultTtl());

        if (null !== $tag) {
            $node->addTag($tag);
        }
        if (null !== $parent) {
            $parent->addChild($node);
        }

        if ($pushToTop) {
            /*
             * Force position before first item
             */
            $node->setPosition(0.5);
        }

        $sourceClass = NodeType::getGeneratedEntitiesNamespace() . "\\" . $nodeType->getSourceEntityClassName();

        /** @var NodesSources $source */
        $source = new $sourceClass($node, $translation);
        $source->setTitle($name);
        $source->setPublishedAt(new \DateTime());
        $node->setNodeName($this->nodeNamePolicy->getCanonicalNodeName($source));

        $this->entityManager->persist($node);
        $this->entityManager->persist($source);
        $this->entityManager->flush();

        return $source;
    }

    /**
     * Try to generate a unique node from request variables.
     *
     * @param Request $request
     *
     * @return NodesSources
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateFromRequest(Request $request)
    {
        $pushToTop = false;

        if ($request->get('pushTop') == 1) {
            $pushToTop = true;
        }

        if ($request->get('tagId') > 0) {
            $tag = $this->entityManager
                        ->find(Tag::class, (int) $request->get('tagId'));
        } else {
            $tag = null;
        }

        if ($request->get('parentNodeId') > 0) {
            $parent = $this->entityManager->find(
                Node::class,
                (int) $request->get('parentNodeId')
            );
        } else {
            $parent = null;
        }

        if ($request->get('nodeTypeId') > 0) {
            /** @var NodeType|null $nodeType */
            $nodeType = $this->entityManager->find(
                NodeType::class,
                (int) $request->get('nodeTypeId')
            );

            if (null !== $nodeType) {
                $translation = null;

                if ($request->get('translationId') > 0) {
                    /** @var Translation $translation */
                    $translation = $this->entityManager->find(
                        Translation::class,
                        (int) $request->get('translationId')
                    );
                } else {
                    /** @var Translation $translation */
                    $translation = $this->entityManager
                                        ->getRepository(Translation::class)
                                        ->findDefault();
                }

                return $this->generate(
                    $nodeType,
                    $translation,
                    $parent,
                    $tag,
                    $pushToTop
                );
            } else {
                throw new BadRequestHttpException("Node-type does not exist.");
            }
        } else {
            throw new BadRequestHttpException("No node-type ID has been given.");
        }
    }
}
