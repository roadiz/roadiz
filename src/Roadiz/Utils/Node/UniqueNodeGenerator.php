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
 * @file UniqueNodeGenerator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Node;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class UniqueNodeGenerator
 * @package RZ\Roadiz\Utils\Node
 */
class UniqueNodeGenerator
{
    protected $entityManager;

    /**
     * UniqueNodeGenerator constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Generate a node with a unique name.
     *
     * This method flush entity-manager.
     *
     * @param  NodeType    $nodeType
     * @param  Translation $translation
     * @param  Node|null   $parent
     * @param  Tag|null    $tag
     * @param  boolean     $pushToTop
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function generate(
        NodeType $nodeType,
        Translation $translation,
        Node $parent = null,
        Tag $tag = null,
        $pushToTop = false
    ) {
        $name = $nodeType->getDisplayName() . " " . uniqid();
        $node = new Node($nodeType);
        $node->setTtl($node->getNodeType()->getDefaultTtl());
        $node->setNodeName($name);

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

        /** @var \RZ\Roadiz\Core\Entities\NodesSources $source */
        $source = new $sourceClass($node, $translation);
        $source->setTitle($name);
        $source->setPublishedAt(new \DateTime());

        $this->entityManager->persist($node);
        $this->entityManager->persist($source);
        $this->entityManager->flush();

        return $source;
    }

    /**
     * Try to generate a unique node from request variables.
     *
     * @param  Request $request
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
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
