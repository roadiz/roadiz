<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TagObjectConstructor;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TypedObjectConstructorInterface;

/**
 * Class TagsImporter.
 *
 * @package RZ\Roadiz\CMS\Importers
 */
class TagsImporter implements EntityImporterInterface, ContainerAwareInterface
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
        return $entityClass === Tag::class;
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
            Tag::class,
            'json',
            DeserializationContext::create()
                ->setAttribute(TypedObjectConstructorInterface::PERSIST_NEW_OBJECTS, true)
                ->setAttribute(TypedObjectConstructorInterface::FLUSH_NEW_OBJECTS, true)
                ->setAttribute(TagObjectConstructor::EXCEPTION_ON_EXISTING_TAG, true)
        );

        return true;
    }
}
