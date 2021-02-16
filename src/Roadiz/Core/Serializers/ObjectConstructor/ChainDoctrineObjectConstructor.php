<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

class ChainDoctrineObjectConstructor implements ObjectConstructorInterface
{
    protected ?EntityManagerInterface $entityManager;
    /**
     * @var ArrayCollection<TypedObjectConstructorInterface>
     */
    protected ArrayCollection $typedObjectConstructors;
    protected ObjectConstructorInterface $fallbackConstructor;

    /**
     * @param EntityManagerInterface|null $entityManager
     * @param ObjectConstructorInterface $fallbackConstructor
     */
    public function __construct(?EntityManagerInterface $entityManager, ObjectConstructorInterface $fallbackConstructor)
    {
        $this->entityManager = $entityManager;
        $this->typedObjectConstructors = new ArrayCollection();
        $this->fallbackConstructor = $fallbackConstructor;
    }

    /**
     * @param TypedObjectConstructorInterface $typedObjectConstructor
     *
     * @return ChainDoctrineObjectConstructor
     */
    public function add(TypedObjectConstructorInterface $typedObjectConstructor): self
    {
        if (!$this->typedObjectConstructors->contains($typedObjectConstructor)) {
            $this->typedObjectConstructors->add($typedObjectConstructor);
        }
        return $this;
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
        if (null === $this->entityManager) {
            // No ObjectManager found, proceed with normal deserialization
            return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        // Locate possible ClassMetadata
        $classMetadataFactory = $this->entityManager->getMetadataFactory();

        if ($classMetadataFactory->isTransient($metadata->name)) {
            // No ClassMetadata found, proceed with normal deserialization
            return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        // Managed entity, check for proxy load
        if (!\is_array($data)) {
            // Single identifier, load proxy
            return $this->entityManager->getReference($metadata->name, $data);
        }

        /** @var TypedObjectConstructorInterface $typedObjectConstructor */
        foreach ($this->typedObjectConstructors as $typedObjectConstructor) {
            if ($typedObjectConstructor->supports($metadata->name, $data)) {
                return $typedObjectConstructor->construct(
                    $visitor,
                    $metadata,
                    $data,
                    $type,
                    $context
                );
            }
        }

        // Fallback to default constructor if missing identifier(s)
        $classMetadata = $this->entityManager->getClassMetadata($metadata->name);
        $identifierList = [];

        foreach ($classMetadata->getIdentifierFieldNames() as $name) {
            if (isset($metadata->propertyMetadata[$name]) &&
                isset($metadata->propertyMetadata[$name]->serializedName)) {
                $dataName = $metadata->propertyMetadata[$name]->serializedName;
            } else {
                $dataName = $name;
            }

            if (!array_key_exists($dataName, $data)) {
                return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
            }
            $identifierList[$name] = $data[$dataName];
        }

        // Entity update, load it from database
        $object = $this->entityManager->find($metadata->name, $identifierList);

        if (null === $object) {
            return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        $this->entityManager->initializeObject($object);

        return $object;
    }
}
