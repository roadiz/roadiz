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

use Doctrine\ORM\EntityManager;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Serializers\NodeJsonSerializer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * {@inheritdoc}
 */
class NodesImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing node and node source.
     *
     * @param string $serializedData
     * @param EntityManager $em
     *
     * @return bool
     */
    public static function importJsonFile($serializedData, EntityManager $em)
    {
        $serializer = new NodeJsonSerializer($em);
        $nodes = $serializer->deserialize($serializedData);

        foreach ($nodes as $node) {
            static::browseTree($node, $em);
        }

        return true;
    }

    /**
     * @param Node          $node
     * @param EntityManager $em
     *
     * @return Node|null
     */
    protected static function browseTree($node, EntityManager $em)
    {
        try {
            /*
             * Test if node already exists against its nodeName
             */
            $existing = $em->getRepository('RZ\Roadiz\Core\Entities\Node')
                           ->findOneByNodeName($node->getNodeName());
            if (null !== $existing) {
                return null;
            }

            /** @var Node[] $childObj */
            $childObj = [];

            /** @var NodesSources[] $sourceObj */
            $sourceObj = [];

            foreach ($node->getChildren() as $child) {
                $childObj[] = static::browseTree($child, $em);
            }
            $node->getChildren()->clear();
            foreach ($node->getNodeSources() as $nodeSource) {
                $trans = $em->getRepository("RZ\Roadiz\Core\Entities\Translation")
                            ->findOneByLocale($nodeSource->getTranslation()->getLocale());

                if (empty($trans)) {
                    $trans = new Translation();
                    $trans->setLocale($nodeSource->getTranslation()->getLocale());
                    $trans->setName(Translation::$availableLocales[$nodeSource->getTranslation()->getLocale()]);
                    $em->persist($trans);
                }
                $nodeSource->setTranslation($trans);
                foreach ($nodeSource->getUrlAliases() as $alias) {
                    $em->persist($alias);
                }
                $nodeSource->setNode(null);
                $em->persist($nodeSource);
                $sourceObj[] = $nodeSource;
            }

            $em->persist($node);
            foreach ($childObj as $child) {
                $child->setParent($node);
            }
            foreach ($sourceObj as $nodeSource) {
                $nodeSource->setNode($node);
            }
            $em->flush();

            return $node;
        } catch (UniqueConstraintViolationException $e) {
            return null;
        }
    }
}
