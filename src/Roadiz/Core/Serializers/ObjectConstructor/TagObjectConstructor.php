<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

class TagObjectConstructor extends AbstractTypedObjectConstructor
{
    const EXCEPTION_ON_EXISTING_TAG = 'exception_on_existing_tag';
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return $className === Tag::class && array_key_exists('tagName', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data, DeserializationContext $context): ?object
    {
        if (null === $data['tagName'] || $data['tagName'] === '') {
            throw new ObjectConstructionException('Tag name can not be empty');
        }
        $tag = $this->entityManager
            ->getRepository(Tag::class)
            ->findOneByTagName($data['tagName']);

        if (null !== $tag &&
            $context->hasAttribute(static::EXCEPTION_ON_EXISTING_TAG) &&
            true === $context->hasAttribute(static::EXCEPTION_ON_EXISTING_TAG)
        ) {
            throw new EntityAlreadyExistsException('Tag already exists in database.');
        }

        return $tag;
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        if ($object instanceof Tag) {
            $object->setTagName($data['tagName']);
        }
    }
}
