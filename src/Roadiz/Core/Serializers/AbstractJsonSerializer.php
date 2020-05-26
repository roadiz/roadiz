<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

/**
 * Define basic serialize operations for Json data type.
 */
abstract class AbstractJsonSerializer implements SerializerInterface
{
    /**
     * Serializes data.
     *
     * @param mixed $obj
     *
     * @return string
     */
    public function serialize($obj)
    {
        $data = $this->toArray($obj);
        return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
