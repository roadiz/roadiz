<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

/**
 * EntitySerializer that implements simple serialization/deserialization methods.
 */
interface SerializerInterface
{

    /**
     * Serializes data.
     *
     * @param mixed $obj
     *
     * @return mixed
     */
    public function serialize($obj);


    /**
     * Create a simple associative array with an entity.
     *
     * @param mixed $obj
     *
     * @return array
     */
    public function toArray($obj);

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $string Input to deserialize
     *
     * @return mixed
     */
    public function deserialize($string);
}
