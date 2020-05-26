<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TypedObjectConstructorInterface;

/**
 * Class GroupsImporter
 *
 * @package RZ\Roadiz\CMS\Importers
 */
class GroupsImporter implements EntityImporterInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * GroupsImporter constructor.
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
        return $entityClass === Group::class;
    }

    /**
     * @inheritDoc
     */
    public function import(string $serializedData): bool
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $serializer->deserialize(
            $serializedData,
            'array<' . Group::class . '>',
            'json',
            DeserializationContext::create()
                ->setAttribute(TypedObjectConstructorInterface::PERSIST_NEW_OBJECTS, true)
                ->setAttribute(TypedObjectConstructorInterface::FLUSH_NEW_OBJECTS, true)
        );
        return true;
    }
}
