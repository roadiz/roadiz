<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file RoleJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for Role.
 */
class RoleJsonSerializer extends AbstractJsonSerializer
{

    /**
     * Create a simple associative array with Role entity.
     *
     * @param RZ\Renzo\Core\Entities\Role $role
     *
     * @return array
     */
    public static function toArray($role)
    {
        $data = array();

        $data['name'] = $role->getName();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return RZ\Renzo\Core\Entities\Role
     */
    public static function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array('name'));

        $serializer = new Serializer(array($normalizer), array($encoder));

        return $serializer->deserialize($jsonString, 'RZ\Renzo\Core\Entities\Role', 'json');
    }
}
