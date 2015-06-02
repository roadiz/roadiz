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

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

/**
*
*/
class UniqueNodeGenerator
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Generate a node with a unique name.
     *
     * @param  NodeType    $nodeType
     * @param  Translation $translation
     * @param  Node|null   $parent
     * @param  Tag|null    $tag
     * @param  boolean     $pushToTop
     *
     * @return RZ\Roadiz\Core\Entities\NodesSources
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
        $node->setParent($parent);
        $node->setNodeName($name);

        if (null !== $tag) {
            $node->addTag($tag);
        }

        $this->entityManager->persist($node);

        if ($pushToTop) {
            $node->setPosition(0.5);
        }

        $sourceClass = "GeneratedNodeSources\\" . $nodeType->getSourceEntityClassName();
        $source = new $sourceClass($node, $translation);
        $source->setTitle($name);
        $this->entityManager->persist($source);
        $this->entityManager->flush();

        return $source;
    }

    /**
     * Try to generate a unique node from request variables.
     *
     * @param  Request $request
     *
     * @return RZ\Roadiz\Core\Entities\NodesSources
     */
    public function generateFromRequest(Request $request)
    {
        $pushToTop = false;

        if ($request->get('pushTop') == 1) {
            $pushToTop = true;
        }

        if ($request->get('tagId') > 0) {
            $tag = $this->entityManager
                        ->find('RZ\Roadiz\Core\Entities\Tag', (int) $request->get('tagId'));
        } else {
            $tag = null;
        }

        if ($request->get('parentNodeId') > 0) {
            $parent = $this->entityManager->find(
               'RZ\Roadiz\Core\Entities\Node',
               (int) $request->get('parentNodeId')
           );
        } else {
            $parent = null;
        }

        if ($request->get('nodeTypeId') > 0) {
            $nodeType = $this->entityManager->find(
                                 'RZ\Roadiz\Core\Entities\NodeType',
                                 (int) $request->get('nodeTypeId')
                             );

            if (null !== $nodeType) {
                if ($request->get('translationId') > 0) {
                    $translation = $this->entityManager->find(
                        'RZ\Roadiz\Core\Entities\Translation',
                        (int) $request->get('translationId')
                    );
                } elseif (null !== $parent) {
                    $translation = $parent->getNodeSources()->first()->getTranslation();
                }

                if (null === $translation) {
                    $translation = $this->entityManager
                                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
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
                throw new \RuntimeException("Node-type does not exist.", 1);
            }
        } else {
            throw new \RuntimeException("No node-type ID has been given.", 1);
        }
    }
}
