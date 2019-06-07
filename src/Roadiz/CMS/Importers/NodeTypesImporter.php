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
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use RZ\Roadiz\Core\Serializers\NodeTypeJsonSerializer;

/**
 * {@inheritdoc}
 */
class NodeTypesImporter implements ImporterInterface, EntityImporterInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * NodesImporter constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }


    /**
     * @inheritDoc
     */
    public function supports(string $entityClass): bool
    {
        return $entityClass === NodeType::class;
    }

    /**
     * @inheritDoc
     */
    public function import(string $serializedData): bool
    {
        /** @var EntityManager $em */
        $em = $this->get('em');
        /** @var HandlerFactoryInterface $handlerFactory */
        $handlerFactory = $this->get('factory.handler');

        $serializer = new NodeTypeJsonSerializer();
        $nodeType = $serializer->deserialize($serializedData);
        /** @var NodeType $existingNodeType */
        $existingNodeType = $em->getRepository(NodeType::class)
            ->findOneByName($nodeType->getName());

        if ($existingNodeType === null) {
            $em->persist($nodeType);
            $existingNodeType = $nodeType;
            /** @var NodeTypeHandler $nodeTypeHandler */
            $nodeTypeHandler = $handlerFactory->getHandler($existingNodeType);
            $fieldPosition = 1;
            /** @var NodeTypeField $field */
            foreach ($nodeType->getFields() as $field) {
                $em->persist($field);
                $field->setNodeType($nodeType);
                $field->setPosition($fieldPosition);
                $fieldPosition++;
            }
        } else {
            /** @var NodeTypeHandler $nodeTypeHandler */
            $nodeTypeHandler = $handlerFactory->getHandler($existingNodeType);
            $nodeTypeHandler->diff($nodeType);
        }
        $em->flush();
        $nodeTypeHandler->updateSchema();
        return true;
    }

    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param string $serializedData
     * @param EntityManager $em
     *
     * @param HandlerFactoryInterface $handlerFactory
     * @return bool
     * @deprecated
     */
    public static function importJsonFile($serializedData, EntityManager $em, HandlerFactoryInterface $handlerFactory)
    {
        $serializer = new NodeTypeJsonSerializer();
        $nodeType = $serializer->deserialize($serializedData);
        /** @var NodeType $existingNodeType */
        $existingNodeType = $em->getRepository(NodeType::class)
                               ->findOneByName($nodeType->getName());

        if ($existingNodeType === null) {
            $em->persist($nodeType);
            $existingNodeType = $nodeType;
            /** @var NodeTypeHandler $nodeTypeHandler */
            $nodeTypeHandler = $handlerFactory->getHandler($existingNodeType);
            $fieldPosition = 1;
            /** @var NodeTypeField $field */
            foreach ($nodeType->getFields() as $field) {
                $em->persist($field);
                $field->setNodeType($nodeType);
                $field->setPosition($fieldPosition);
                $fieldPosition++;
            }
        } else {
            /** @var NodeTypeHandler $nodeTypeHandler */
            $nodeTypeHandler = $handlerFactory->getHandler($existingNodeType);
            $nodeTypeHandler->diff($nodeType);
        }
        $em->flush();
        $nodeTypeHandler->regenerateEntityClass();
        return true;
    }
}
