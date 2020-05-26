<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Serializer;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Attribute\Model\AttributeGroupInterface;
use RZ\Roadiz\Core\Entities\AttributeGroup;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\AbstractTypedObjectConstructor;

class AttributeGroupObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return ($className === AttributeGroup::class || $className === AttributeGroupInterface::class) &&
            array_key_exists('canonicalName', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data, DeserializationContext $context): ?object
    {
        if (null === $data['canonicalName'] || $data['canonicalName'] === '') {
            throw new ObjectConstructionException('AttributeGroup canonical name can not be empty');
        }
        return $this->entityManager
            ->getRepository(AttributeGroup::class)
            ->findOneByCanonicalName($data['canonicalName']);
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        if ($object instanceof AttributeGroup) {
            $object->setCanonicalName($data['canonicalName']);
        }
    }
}
