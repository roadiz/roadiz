<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Serializer;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Core\Entities\Attribute;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\AbstractTypedObjectConstructor;

class AttributeObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return ($className === Attribute::class || $className === AttributeInterface::class) &&
            array_key_exists('code', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data, DeserializationContext $context): ?object
    {
        if (null === $data['code'] || $data['code'] === '') {
            throw new ObjectConstructionException('Attribute code can not be empty');
        }
        return $this->entityManager
            ->getRepository(Attribute::class)
            ->findOneByCode($data['code']);
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        if ($object instanceof Attribute) {
            $object->setCode($data['code']);
        }
    }
}
