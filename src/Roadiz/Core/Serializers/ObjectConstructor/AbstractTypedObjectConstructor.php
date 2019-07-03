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
 * @file AbstractTypedObjectConstructor.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

abstract class AbstractTypedObjectConstructor implements TypedObjectConstructorInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var ObjectConstructorInterface
     */
    protected $fallbackConstructor;

    /**
     * AttributeConstructor constructor.
     *
     * @param EntityManagerInterface     $entityManager
     * @param ObjectConstructorInterface $fallbackConstructor
     */
    public function __construct(EntityManagerInterface $entityManager, ObjectConstructorInterface $fallbackConstructor)
    {
        $this->entityManager = $entityManager;
        $this->fallbackConstructor = $fallbackConstructor;
    }

    /**
     * @param $data
     *
     * @return object|null
     */
    abstract protected function findObject($data): ?object;

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
        $object = $this->findObject($data);

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
                    $this->entityManager->flush($object);
                }
            }
        }

        $this->entityManager->initializeObject($object);

        return $object;
    }
}
