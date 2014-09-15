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
class RoleCollectionJsonSerializer implements SerializerInterface
{
     /**
     * Serializes data into Json.
     *
     * @return string
     */
    public static function serialize($roles)
    {
        $data = array();

        foreach ($roles as $role) {
            $tmp = array();
            $tmp['name'] = $role->getName();
            $data[] = $tmp;
        }


        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
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