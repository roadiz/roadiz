<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TypedObjectConstructorInterface;

/**
 * Class NodeTypesImporter
 *
 * @package RZ\Roadiz\CMS\Importers
 */
class NodeTypesImporter implements EntityImporterInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * NodeTypesImporter constructor.
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
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $nodeType = $serializer->deserialize(
            $serializedData,
            NodeType::class,
            'json',
            DeserializationContext::create()
                ->setAttribute(TypedObjectConstructorInterface::PERSIST_NEW_OBJECTS, true)
                ->setAttribute(TypedObjectConstructorInterface::FLUSH_NEW_OBJECTS, true)
        );

        /** @var HandlerFactoryInterface $handlerFactory */
        $handlerFactory = $this->get('factory.handler');
        /** @var NodeTypeHandler $nodeTypeHandler */
        $nodeTypeHandler = $handlerFactory->getHandler($nodeType);
        $nodeTypeHandler->updateSchema();

        return true;
    }
}
