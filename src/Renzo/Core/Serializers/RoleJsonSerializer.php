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
class RoleJsonSerializer implements SerializerInterface
{

    protected $role;
    /**
     * RoleJsonSerializer's contructor.
     *
     * @param RZ\Renzo\Core\Entities\Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * @return RZ\Renzo\Core\Entities\Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Serializes data.
     *
     * This method does not output a valid JSON string
     * but only a ready-to-encode array. This will be encoded
     * by the parent Node-type serialize method.
     *
     * @return array
     * @see RZ\Renzo\Core\Serializers\GroupJsonSerializer::serialize
     */
    public function serialize()
    {
        $data = array();
        $data['name'] = $this->getRole()->getName();

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
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array('name'));

        $serializer = new Serializer(array($normalizer), array($encoder));

        return $serializer->deserialize($jsonString, 'RZ\Renzo\Core\Entities\Role', 'json');
    }
}