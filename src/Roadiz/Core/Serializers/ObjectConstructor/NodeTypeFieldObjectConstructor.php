<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

class NodeTypeFieldObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return $className === NodeTypeField::class && array_key_exists('name', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data, DeserializationContext $context): ?object
    {
        if (null === $data['name'] || $data['name'] === '') {
            throw new ObjectConstructionException('NodeTypeField name can not be empty');
        }
        if (empty($data['nodeTypeName']) || null === $data['nodeTypeName'] || $data['nodeTypeName'] === '') {
            throw new ObjectConstructionException('nodeTypeName is missing to check duplication.');
        }

        $nodeType = $this->entityManager
            ->getRepository(NodeType::class)
            ->findOneByName($data['nodeTypeName']);

        if (null === $nodeType) {
            /*
             * Do not look for existing fields if node-type does not exist either.
             */
            return null;
        }
        return $this->entityManager
            ->getRepository(NodeTypeField::class)
            ->findOneBy([
                'name' => $data['name'],
                'nodeType' => $nodeType,
            ]);
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        trigger_error('Cannot call fillIdentifier on NodeTypeField', E_USER_WARNING);
    }

    /**
     * @return bool
     */
    protected function canBeFlushed(): bool
    {
        return false;
    }
}
