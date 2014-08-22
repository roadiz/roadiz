<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file EntitySerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Utils;

/**
 * EntitySerializer that implements simple serialization/deserialization methods.
 */
interface SerializerInterface
{

    /**
     * Serializes data.
     *
     * @return void
     */
    public function serialize();

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @return void
     */
    public function deserialize();
}