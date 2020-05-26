<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Serializer;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Attribute\Model\AttributeGroupTranslationInterface;
use RZ\Roadiz\Core\Entities\AttributeGroupTranslation;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\AbstractTypedObjectConstructor;

class AttributeGroupTranslationObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return ($className === AttributeGroupTranslation::class || $className === AttributeGroupTranslationInterface::class) &&
            array_key_exists('translation', $data) &&
            array_key_exists('name', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data, DeserializationContext $context): ?object
    {
        if (null === $data['translation'] || empty($data['translation'])) {
            throw new ObjectConstructionException('AttributeGroupTranslation translation can not be empty');
        }
        if (null === $data['name'] || empty($data['name'])) {
            throw new ObjectConstructionException('AttributeGroupTranslation name can not be empty');
        }
        return $this->entityManager
            ->getRepository(AttributeGroupTranslation::class)
            ->findOneByNameAndLocale(
                $data['name'],
                $data['translation']['locale']
            );
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        if ($object instanceof AttributeGroupTranslation) {
            $object->setName($data['name']);
        }
    }
}
