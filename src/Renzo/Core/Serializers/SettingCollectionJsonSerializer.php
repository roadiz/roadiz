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
use RZ\Renzo\Core\Entities\SettingGroup;
use RZ\Renzo\Core\Entities\NodeTypeField;
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
    /**
     * Serializes data.
     *
     * @return string
     *
     */
    public static function serialize($settingGroup)
    {
        $data = array();
        foreach ($settingGroup as $group) {
            $tmpGroup = array();
            foreach ($group->getSettings() as $setting) {
                $tmp = array(
                    "name" => $setting->getName(),
                    "value" => $setting->getValue(),
                    "visible" => $setting->isVisible(),
                    "type" => $setting->getType(),
                );
                $tmpGroup[] = $tmp;
            }
            $data[$group->getName()] = $tmpGroup;
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
        $groups = json_decode($jsonString, true);
        foreach ($groups as $name => $group)
        {
            $newGroup = new SettingGroup();

            $newGroup->setName($name);
            foreach ($group as $setting) {
                $newSetting = new Setting();
                $newSetting->setName($setting['name']);
                $newSetting->setType($setting['type']);
                if ($setting['type'] == NodeTypeField::DATETIME_T) {
                    $dt = new \DateTime($setting['value']['date'], new \DateTimeZone($setting['value']['timezone']));
                    $newSetting->setValue($dt);
                }
                else {
                    $newSetting->setValue($setting['value']);
                }
                $newSetting->setVisible($setting['visible']);

                $newGroup->addSetting($newSetting);
            }
            $collection[] = $newGroup;
        }

        return $collection;
    }
}