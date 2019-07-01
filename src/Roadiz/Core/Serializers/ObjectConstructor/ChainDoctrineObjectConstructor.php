<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file ChainDoctrineObjectConstructor.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

class ChainDoctrineObjectConstructor implements ObjectConstructorInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var ArrayCollection<TypedObjectConstructorInterface>
     */
    protected $typedObjectConstructors;
    /**
     * @var ObjectConstructorInterface
     */
    protected $fallbackConstructor;

    /**
     * ChainPersistableObjectConstructor constructor.
     *
     * @param EntityManagerInterface|null     $entityManager
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
        if (!$this->entityManager) {
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
            if (isset($metadata->propertyMetadata[$name])) {
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
