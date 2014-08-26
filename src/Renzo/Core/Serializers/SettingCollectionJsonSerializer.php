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
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for Setting.
 */
class SettingCollectionJsonSerializer implements SerializerInterface
{

    protected $settings;
    /**
     * SettingCollectionJsonSerializer's contructor.
     *
     * @param Doctrine\Common\Collections\ArrayCollection $settings
     */
    public function __construct(ArrayCollection $settings)
    {
        $this->setting = $setting;
    }

    /**
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Serializes data.
     *
     * @return string
     * @see RZ\Renzo\Core\Serializers\GroupJsonSerializer::serialize
     */
    public function serialize()
    {
        $data = array();
        foreach ($this->getSettings() as $setting) {
            $data[] = array(
                "name" => $setting->getName(),
                "value" => $setting->getValue(),
                "visible" => $setting->isVisible(),
                "type" => $setting->getType(),
            );
        }

        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * Deserializes a json file into a readable ArrayCollection of setting.
     *
     * @param string $jsonString
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public static function deserialize($jsonString)
    {
        $collection = new ArrayCollection();
        $array = json_decode($jsonString, true);

        foreach ($array as $settingAssoc) {
            $setting = new Setting();
            $setting->setName($settingAssoc['name']);
            $setting->setValue($settingAssoc['value']);
            $setting->setVisible($settingAssoc['visible']);
            $setting->setType($settingAssoc['type']);

            $collection->add($setting);
        }

        return $collection;
    }
}