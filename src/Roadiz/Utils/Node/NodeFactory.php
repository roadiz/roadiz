<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeFactory.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\ORM\EntityManager;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Repositories\UrlAliasRepository;
use RZ\Roadiz\Utils\StringHandler;

final class NodeFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * NodeFactory constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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
        $nodeName = StringHandler::slugify($title);
        if (empty($nodeName)) {
            throw new \RuntimeException('Node name is empty.');
        }
        if (mb_strlen($nodeName) > 250) {
            throw new \InvalidArgumentException(sprintf('Node name "%s" is too long.', $nodeName));
        }
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('em');
        /** @var NodeRepository $repository */
        $repository = $entityManager->getRepository(Node::class)
            ->setDisplayingAllNodesStatuses(true);

        if (true === $repository->exists($nodeName)) {
            $nodeName .= '-' . uniqid();
        }

        if ($node === null && $type === null) {
            throw new \RuntimeException('Cannot create node from null NodeType and null Node.');
        }

        if ($translation === null) {
            $translation = $this->get('defaultTranslation');
        }

        if ($node === null) {
            $node = new Node($type);
        }

        $node->setNodeName($nodeName);
        $node->setTtl($node->getNodeType()->getDefaultTtl());
        if (null !== $parent) {
            $node->setParent($parent);
        }
        $entityManager->persist($node);

        $sourceClass = $node->getNodeType()->getSourceEntityFullQualifiedClassName();
        /** @var NodesSources $source */
        $source = new $sourceClass($node, $translation);
        $source->setTitle($title);
        $source->setPublishedAt(new \DateTime());
        $entityManager->persist($source);

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
        $repository = $this->get('em')->getRepository(UrlAlias::class);
        if (false === $repository->exists($urlAlias)) {
            $alias = new UrlAlias($node->getNodeSources()->first());
            $alias->setAlias($urlAlias);
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('em');
            $entityManager->persist($alias);
        }

        return $node;
    }
}
