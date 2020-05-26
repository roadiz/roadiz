<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\SettingGroup;

/**
 * Serialization class for Setting.
 * @deprecated Use Serializer service.
 */
class SettingCollectionJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with
     * an ArrayCollection of SettingGroup.
     *
     * @param ArrayCollection $settingGroups
     *
     * @return array
     */
    public function toArray($settingGroups)
    {
        $settingSerializer = new SettingJsonSerializer();
        $data = [];

        foreach ($settingGroups as $group) {
            $tmpGroup = [];

            $tmpGroup['name'] = $group->getName();
            $tmpGroup['inMenu'] = $group->isInMenu();
            $tmpGroup['settings'] = [];

            foreach ($group->getSettings() as $setting) {
                $tmpGroup['settings'][] = $settingSerializer->toArray($setting);
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
     * @return ArrayCollection
     * @throws \Exception
     */
    public function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $collection = new ArrayCollection();
        $groups = json_decode($jsonString, true);

        foreach ($groups as $group) {
            if (!empty($group['name'])) {
                $newGroup = new SettingGroup();
                $newGroup->setName($group['name']);

                if (isset($group['inMenu'])) {
                    $newGroup->setInMenu($group['inMenu']);
                }

                if (!empty($group['settings'])) {
                    foreach ($group['settings'] as $setting) {
                        // do not use !empty on type as it can be 0.
                        if (!empty($setting['name']) && isset($setting['type'])) {
                            $newSetting = new Setting();
                            $newSetting->setName($setting['name']);
                            $newSetting->setType($setting['type']);
                            if ($setting['type'] == NodeTypeField::DATETIME_T) {
                                $dt = new \DateTime(
                                    $setting['value']['date'],
                                    new \DateTimeZone($setting['value']['timezone'])
                                );
                                $newSetting->setValue($dt);
                            } else {
                                $newSetting->setValue($setting['value']);
                            }
                            $newSetting->setVisible($setting['visible']);
                            if (isset($setting['default_values'])) {
                                $newSetting->setDefaultValues($setting['default_values']);
                            }
                            if (isset($setting['description'])) {
                                $newSetting->setDescription($setting['description']);
                            }
                            $newGroup->addSetting($newSetting);
                            $newSetting->setSettingGroup($newGroup);
                        }
                    }
                }
                $collection[] = $newGroup;
            }
        }

        return $collection;
    }
}
