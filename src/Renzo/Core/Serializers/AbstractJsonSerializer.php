<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file AbstractJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\Serializers;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

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
    public static function serialize($obj)
    {
        $data = static::toArray($obj);

        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
    }
}
