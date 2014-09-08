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
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for Group.
 */
class GroupCollectionJsonSerializer implements SerializerInterface
{

    /**
     * Serializes data into Json.
     *
     * @return string
     */
    public static function serialize($groups)
    {
        $data = array();

        foreach ($groups as $group) {
            $tmp = array();

            $tmp['name'] = $group->getName();
            $tmp['roles'] = array();

            foreach ($group->getRolesEntities() as $role) {
                $tmp['roles'][] = array('name' => $role->getName());
            }
            $data[] = $tmp;
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
        $collection = new ArrayCollection();
        $array = json_decode($string, true);

        foreach ($array as $groupAssoc) {
            $group = new Group();
            $group->setName($groupAssoc['name']);

            foreach ($groupAssoc["roles"] as $role) {
                $role = Kernel::getInstance()->em()->getRepository('RZ\Renzo\Core\Entities\Role')->findOneByName($role['name']);
                $group->addRole($role);

            }

            $collection[] = $group;
        }

        return $collection;
    }
}