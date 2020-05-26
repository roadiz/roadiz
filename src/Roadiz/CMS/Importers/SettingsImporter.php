<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TypedObjectConstructorInterface;

/**
 * Class SettingsImporter
 *
 * @package RZ\Roadiz\CMS\Importers
 */
class SettingsImporter implements EntityImporterInterface, ContainerAwareInterface
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
        return $entityClass === Setting::class;
    }

    /**
     * @inheritDoc
     */
    public function import(string $serializedData): bool
    {
        /** @var EntityManager $em */
        $em = $this->get('em');
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $serializer->deserialize(
            $serializedData,
            'array<' . Setting::class . '>',
            'json',
            DeserializationContext::create()
                ->setAttribute(TypedObjectConstructorInterface::PERSIST_NEW_OBJECTS, true)
                ->setAttribute(TypedObjectConstructorInterface::FLUSH_NEW_OBJECTS, true)
        );

        // Clear result cache
        $cacheDriver = $em->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null && $cacheDriver instanceof CacheProvider) {
            $cacheDriver->deleteAll();
        }

        return true;
    }
}
