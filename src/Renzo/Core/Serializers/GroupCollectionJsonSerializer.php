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
class GroupCollectionJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with
     * an ArrayCollection of Group.
     *
     * @param Doctrine\Common\Collections\ArrayCollection $groups
     *
     * @return array
     */
    public static function toArray($groups)
    {
        $data = array();

        foreach ($groups as $group) {
            $data[] = GroupJsonSerializer::toArray($group);
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas
     * @param string $string
     *
     * @return ArrayCollection
     */
    public static function deserialize($string)
    {
        if ($string == "") {
            throw new \Exception('File is empty.');
        }
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
