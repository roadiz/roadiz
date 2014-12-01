<?php
/**
 * Copyright © 2014, REZO ZERO
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file SettingCollectionJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Serializers\EntitySerializer;
use RZ\Roadiz\Core\Kernel;

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
