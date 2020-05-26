<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Core\Entities\Node;

class NodeObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return $className === Node::class && array_key_exists('nodeName', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data, DeserializationContext $context): ?object
    {
        if (null === $data['nodeName'] || $data['nodeName'] === '') {
            throw new ObjectConstructionException('Node name can not be empty');
        }
        return $this->entityManager
            ->getRepository(Node::class)
            ->setDisplayingAllNodesStatuses(true)
            ->findOneByNodeName($data['nodeName']);
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        if ($object instanceof Node) {
            $object->setNodeName($data['nodeName']);
        }
    }
}
