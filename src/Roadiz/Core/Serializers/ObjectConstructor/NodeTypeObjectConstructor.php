<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Core\Entities\NodeType;

class NodeTypeObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return $className === NodeType::class && array_key_exists('name', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data, DeserializationContext $context): ?object
    {
        if (null === $data['name'] || $data['name'] === '') {
            throw new ObjectConstructionException('NodeType name can not be empty');
        }
        return $this->entityManager
            ->getRepository(NodeType::class)
            ->findOneByName($data['name']);
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        if ($object instanceof NodeType) {
            $object->setName($data['name']);
        }
    }
}
