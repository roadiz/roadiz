<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

abstract class AbstractTypedObjectConstructor implements TypedObjectConstructorInterface
{
    protected ObjectManager $entityManager;
    protected ObjectConstructorInterface $fallbackConstructor;

    /**
     * @param ObjectManager $entityManager
     * @param ObjectConstructorInterface $fallbackConstructor
     */
    public function __construct(ObjectManager $entityManager, ObjectConstructorInterface $fallbackConstructor)
    {
        $this->entityManager = $entityManager;
        $this->fallbackConstructor = $fallbackConstructor;
    }

    /**
     * @param mixed $data
     * @param DeserializationContext $context
     *
     * @return object|null
     */
    abstract protected function findObject($data, DeserializationContext $context): ?object;

    /**
     * @param object $object
     * @param array  $data
     */
    abstract protected function fillIdentifier(object $object, array $data): void;

    /**
     * @return bool
     */
    protected function canBeFlushed(): bool
    {
        return true;
    }
    /**
     * @inheritDoc
     */
    public function construct(
        DeserializationVisitorInterface $visitor,
        ClassMetadata $metadata,
        $data,
        array $type,
        DeserializationContext $context
    ): ?object {
        // Entity update, load it from database
        $object = $this->findObject($data, $context);

        if (null !== $object &&
            $context->hasAttribute(static::EXCEPTION_ON_EXISTING) &&
            true === $context->hasAttribute(static::EXCEPTION_ON_EXISTING)
        ) {
            throw new EntityAlreadyExistsException('Object already exists in database.');
        }

        if (null === $object) {
            $object = $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
            if ($context->hasAttribute(static::PERSIST_NEW_OBJECTS) &&
                true === $context->hasAttribute(static::PERSIST_NEW_OBJECTS)) {
                $this->entityManager->persist($object);
            }

            if ($this->canBeFlushed()) {
                /*
                 * If we need to fetch related entities, we can flush light objects with
                 * at least their identifier key filled.
                 */
                $this->fillIdentifier($object, $data);

                if ($context->hasAttribute(static::FLUSH_NEW_OBJECTS) &&
                    true === $context->hasAttribute(static::FLUSH_NEW_OBJECTS)) {
                    $this->entityManager->flush();
                }
            }
        }

        $this->entityManager->initializeObject($object);

        return $object;
    }
}
