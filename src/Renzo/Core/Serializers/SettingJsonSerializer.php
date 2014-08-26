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
class SettingJsonSerializer implements SerializerInterface
{

    protected $setting;
    /**
     * SettingJsonSerializer's contructor.
     *
     * @param RZ\Renzo\Core\Entities\Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * @return RZ\Renzo\Core\Entities\Setting
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * Serializes data.
     *
     * This method does not output a valid JSON string
     * but only a ready-to-encode array. This will be encoded
     * by the parent GroupJsonSerialize method.
     *
     * @return array
     * @see RZ\Renzo\Core\Serializers\GroupJsonSerializer::serialize
     */
    public function serialize()
    {
        $data = array();
        $data['name'] = $this->getSetting()->getName();
        $data['value'] = $this->getSetting()->getValue();
        $data['type'] = $this->getSetting()->getType();
        $data['visible'] = $this->getSetting()->isVisible();

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