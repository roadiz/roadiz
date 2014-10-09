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
class SettingCollectionJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with
     * an ArrayCollection of SettingGroup.
     *
     * @param Doctrine\Common\Collections\ArrayCollection $settingGroup
     *
     * @return array
     */
    public static function toArray($settingGroups)
    {
        $data = array();

        foreach ($settingGroups as $group) {
            $tmpGroup = array();

            $tmpGroup['name'] = $group->getName();
            $tmpGroup['inMenu'] = $group->isInMenu();
            $tmpGroup['settings'] = array();

            foreach ($group->getSettings() as $setting) {
                 $tmpGroup['settings'][] = SettingJsonSerializer::toArray($setting);
            }

            $data[] = $tmpGroup;
        }

        return $data;
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
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $collection = new ArrayCollection();
        $groups = json_decode($jsonString, true);
        foreach ($groups as $group) {

            $newGroup = new SettingGroup();
            $newGroup->setName($group['name']);
            $newGroup->setInMenu($group['inMenu']);

            foreach ($group['settings'] as $setting) {
                $newSetting = new Setting();
                $newSetting->setName($setting['name']);
                $newSetting->setType($setting['type']);
                if ($setting['type'] == NodeTypeField::DATETIME_T) {
                    $dt = new \DateTime($setting['value']['date'], new \DateTimeZone($setting['value']['timezone']));
                    $newSetting->setValue($dt);
                } else {
                    $newSetting->setValue($setting['value']);
                }
                $newSetting->setVisible($setting['visible']);

                $newGroup->addSetting($newSetting);
                $newSetting->setSettingGroup($newGroup);
            }
            $collection[] = $newGroup;
        }

        return $collection;
    }
}
