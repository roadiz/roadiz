<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use JMS\Serializer\Construction\ObjectConstructorInterface;

interface TypedObjectConstructorInterface extends ObjectConstructorInterface
{
    const PERSIST_NEW_OBJECTS = 'persist_on_deserialize';
    const FLUSH_NEW_OBJECTS = 'flush_on_deserialize';
    const EXCEPTION_ON_EXISTING = 'exception_on_existing';
    /**
     * @param string $className
     * @param array $data
     *
     * @return bool
     */
    public function supports(string $className, array $data): bool;
}
