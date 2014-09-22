<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file SettingJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\Setting;
use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for Setting.
 */
class SettingJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with Setting
     * entity.
     *
     * @param RZ\Renzo\Core\Entities\Setting $setting
     *
     * @return array
     */
    public static function toArray($setting)
    {
        $data = array();

        $data['name'] = $setting->getName();
        $data['value'] = $setting->getValue();
        $data['type'] = $setting->getType();
        $data['visible'] = $setting->isVisible();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return RZ\Renzo\Core\Entities\Setting
     */
    public static function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'name',
            'value',
            'type',
            'visible'
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));

        return $serializer->deserialize($jsonString, 'RZ\Renzo\Core\Entities\Setting', 'json');
    }
}
