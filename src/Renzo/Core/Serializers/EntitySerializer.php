<?php 

namespace RZ\Renzo\Core\Utils;

/**
 * EntitySerializer that implements simple serialization/deserialization methods.
 */
interface EntitySerializer {
    /**
     * Serializes data 
     * @return void         
     */
    public function serialize();

    /**
     * Deserializes a json file into a readable array of datas
     * @return void
     */
    public function deserialize();
}