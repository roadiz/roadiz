<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\TypedObjectConstructorInterface;

/**
 * @package RZ\Roadiz\CMS\Importers
 */
class SettingsImporter implements EntityImporterInterface
{
    private ManagerRegistry $managerRegistry;
    private Serializer $serializer;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param Serializer $serializer
     */
    public function __construct(ManagerRegistry $managerRegistry, Serializer $serializer)
    {
        $this->managerRegistry = $managerRegistry;
        $this->serializer = $serializer;
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
        $this->serializer->deserialize(
            $serializedData,
            'array<' . Setting::class . '>',
            'json',
            DeserializationContext::create()
                ->setAttribute(TypedObjectConstructorInterface::PERSIST_NEW_OBJECTS, true)
                ->setAttribute(TypedObjectConstructorInterface::FLUSH_NEW_OBJECTS, true)
        );

        $manager = $this->managerRegistry->getManagerForClass(Setting::class);
        if ($manager instanceof EntityManagerInterface) {
            // Clear result cache
            $cacheDriver = $manager->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver instanceof CacheProvider) {
                $cacheDriver->deleteAll();
            }
        }

        return true;
    }
}
