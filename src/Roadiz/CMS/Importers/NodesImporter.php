<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file NodesImporter.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Importers;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Serializers\NodeJsonSerializer;

/**
 * {@inheritdoc}
 */
class NodesImporter implements ImporterInterface
{
    protected static $usedTranslations;

    /**
     * Import a Json file (.rzt) containing node and node source.
     *
     * @param string $serializedData
     * @param EntityManager $em
     * @param HandlerFactoryInterface $handlerFactory
     * @return bool
     */
    public static function importJsonFile($serializedData, EntityManager $em, HandlerFactoryInterface $handlerFactory)
    {
        static::$usedTranslations = [];
        $serializer = new NodeJsonSerializer($em);
        $nodes = $serializer->deserialize($serializedData);

        try {
            foreach ($nodes as $node) {
                static::browseTree($node, $em);
            }

            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new EntityAlreadyExistsException($e->getMessage());
        }

        return true;
    }

    /**
     * @param Node $node
     * @param EntityManager $em
     * @return null|Node
     * @throws EntityAlreadyExistsException
     */
    protected static function browseTree(Node $node, EntityManager $em)
    {
        /*
         * Test if node already exists against its nodeName
         */
        $existing = $em->getRepository('RZ\Roadiz\Core\Entities\Node')
                       ->setDisplayingNotPublishedNodes(true)
                       ->findOneByNodeName($node->getNodeName());
        if (null !== $existing) {
            throw new EntityAlreadyExistsException('"' . $node->getNodeName() . '" already exists.');
        }

        /** @var Node $child */
        foreach ($node->getChildren() as $child) {
            static::browseTree($child, $em);
        }
        $em->persist($node);

        /** @var NodesSources $nodeSource */
        foreach ($node->getNodeSources() as $nodeSource) {
            /** @var Translation|null $trans */
            $trans = $em->getRepository('RZ\Roadiz\Core\Entities\Translation')
                        ->findOneByLocale($nodeSource->getTranslation()->getLocale());

            if (null === $trans &&
                !empty(static::$usedTranslations[$nodeSource->getTranslation()->getLocale()])) {
                $trans = static::$usedTranslations[$nodeSource->getTranslation()->getLocale()];
            }

            if (null === $trans) {
                $trans = new Translation();
                $trans->setLocale($nodeSource->getTranslation()->getLocale());
                $trans->setName(Translation::$availableLocales[$nodeSource->getTranslation()->getLocale()]);
                $em->persist($trans);

                static::$usedTranslations[$nodeSource->getTranslation()->getLocale()] = $trans;
            }
            $nodeSource->setTranslation($trans);
            foreach ($nodeSource->getUrlAliases() as $alias) {
                $em->persist($alias);
            }

            $em->persist($nodeSource);
        }

        return $node;
    }
}
