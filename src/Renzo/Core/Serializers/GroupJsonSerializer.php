<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file GroupJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for Group.
 */
class GroupJsonSerializer implements SerializerInterface
{

    protected $group;

    /**
     * GroupSerializer's constructor.
     * @param RZ\Renzo\Core\Entities\Group $group
     */
    public function __construct(NodeType $group)
    {
        $this->group = $group;
    }

    /**
     * @return RZ\Renzo\Core\Entities\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Serializes data into Json.
     *
     * @return string
     */
    public function serialize()
    {
        $data = array();

        $data['name'] = $this->getName();
        $data['roles'] = array();

        foreach ($this->getGroup()->getRoles() as $role) {
            $roleSerializer = new RoleJsonSerializer($role);
            $data['roles'][] = $roleSerializer->serialize();
        }

        if (defined(JSON_PRETTY_PRINT)) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * Deserializes a Json into readable datas
     * @param string $string
     *
     * @return RZ\Renzo\Core\Entities\Group
     */
    public static function deserialize($string)
    {
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'name',
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));
        $group = $serializer->deserialize($string, 'RZ\Renzo\Core\Entities\Group', 'json');

        /*
         * Importing Roles.
         *
         * We need to extract roles from group and to re-encode them
         * to pass to RoleJsonSerializer.
         */
        $tempArray = json_decode($string, true);

        foreach ($tempArray['roles'] as $roleAssoc) {
            $role = RoleJsonSerializer::deserialize(json_encode($roleAssoc));
            $group->addRole($role);
        }

        return $group;
    }
}