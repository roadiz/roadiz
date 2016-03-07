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
 * @file NodeTypesImporter.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Importers;

use Doctrine\ORM\EntityManager;

use RZ\Roadiz\Core\Serializers\NodeTypeJsonSerializer;

/**
 * {@inheritdoc}
 */
class NodeTypesImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param string $serializedData
     * @param EntityManager $em
     *
     * @return bool
     */
    public static function importJsonFile($serializedData, EntityManager $em)
    {
        $return = false;
        $serializer = new NodeTypeJsonSerializer();
        $nodeType = $serializer->deserialize($serializedData);
        $existingNodeType = $em->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                               ->findOneByName($nodeType->getName());
        if ($existingNodeType === null) {
            $em->persist($nodeType);
            $existingNodeType = $nodeType;
            foreach ($nodeType->getFields() as $field) {
                $em->persist($field);
                $field->setNodeType($nodeType);
            }
        } else {
            $existingNodeType->getHandler()->diff($nodeType);
        }
        $em->flush();
        $existingNodeType->getHandler()->regenerateEntityClass();
        return $return;
    }
}
