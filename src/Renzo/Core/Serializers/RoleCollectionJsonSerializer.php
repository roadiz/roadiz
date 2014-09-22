<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file RoleCollectionJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\Role;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for Role.
 */
class RoleCollectionJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with
     * an ArrayCollection of Role.
     *
     * @param Doctrine\Common\Collections\ArrayCollection $roles
     *
     * @return array
     */
    public static function toArray($roles)
    {
        $data = array();

        foreach ($roles as $role) {
            $data[] = RoleJsonSerializer::toArray($role);
        }

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return ArrayCollection
     */
    public static function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $roles = json_decode($jsonString, true);
        $data = new ArrayCollection();
        foreach ($roles as $role) {
            $tmp = Kernel::getInstance()->em()->getRepository('RZ\Renzo\Core\Entities\Role')->findOneByName($role['name']);
            $data[] = $tmp;
        }
        return $data;
    }
}
