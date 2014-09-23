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
class GroupJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with Group entity.
     *
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return array
     */
    public static function toArray($group)
    {
        $data = array();

        $data['name'] = $group->getName();
        $data['roles'] = array();

        foreach ($group->getRolesEntities() as $role) {
            $data['roles'][] = RoleJsonSerializer::toArray($role);
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas
     * @param string $string
     *
     * @return RZ\Renzo\Core\Entities\Group
     */
    public static function deserialize($string)
    {
        if ($string == "") {
            throw new \Exception('File is empty.');
        }
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
            $role = Kernel::getInstance()->em()
                                         ->getRepository('RZ\Renzo\Core\Entities\Role')
                                         ->findOneByName($role->getName());
            $group->addRole($role);
        }
        $data = array();
        $data[] = $group;
        return $data;
    }
}
