<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file SettingsImporter.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Importers;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Serializers\SettingCollectionJsonSerializer;

/**
 * {@inheritdoc}
 */
class SettingsImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param string $serializedData
     * @param EntityManager $em
     * @param HandlerFactoryInterface $handlerFactory
     * @return bool
     */
    public static function importJsonFile($serializedData, EntityManager $em, HandlerFactoryInterface $handlerFactory)
    {
        $serializer = new SettingCollectionJsonSerializer();

        /** @var \RZ\Roadiz\Core\Entities\SettingGroup[] $settingGroups */
        $settingGroups = $serializer->deserialize($serializedData);

        $groupsNames = $em->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')->findAllNames();
        $settingsNames = $em->getRepository('RZ\Roadiz\Core\Entities\Setting')->findAllNames();

        $newSettings = [];

        foreach ($settingGroups as $settingGroup) {
            /*
             * Loop over settings to set their group
             * and move them to a temp collection
             */
            /** @var Setting $setting */
            foreach ($settingGroup->getSettings() as $setting) {
                if (!in_array($setting->getName(), $settingsNames)) {
                    // do nothing
                } else {
                    $existingValue = null;

                    if ($setting->getValue() !== "") {
                        $existingValue = $setting->getValue();
                    }
                    $setting = $em->getRepository('RZ\Roadiz\Core\Entities\Setting')
                        ->findOneByName($setting->getName());

                    /*
                     * Force setting value defined in Imported file.
                     */
                    if (null !== $existingValue) {
                        $setting->setValue($existingValue);
                    }
                }
                /*
                 * Set array with setting and the deserialize setting's group
                 * to don't take the existing setting's group
                 */
                $newSettings[] = [$setting, $settingGroup];
                $settingGroup->getSettings()->clear();
            }
        }

        foreach ($newSettings as $settingArray) {
            /** @var \RZ\Roadiz\Core\Entities\SettingGroup $settingGroup */
            $settingGroup = $settingArray[1];

            /** @var \RZ\Roadiz\Core\Entities\Setting $setting */
            $setting = $settingArray[0];

            /*
             * Persist or not group
             */
            if (null !== $settingGroup) {
                if (!in_array($settingGroup->getName(), $groupsNames)) {
                    $em->persist($settingGroup);
                } else {
                    $settingGroup = $em->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                        ->findOneByName($settingGroup->getName());
                }
            }
            /*
             * Add group to setting and persist if don't exist
             */
            $setting->setSettingGroup($settingGroup);
            if ($setting->getId() === null) {
                $em->persist($setting);
            }
        }

        $em->flush();

        // Clear result cache
        $cacheDriver = $em->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null && $cacheDriver instanceof CacheProvider) {
            $cacheDriver->deleteAll();
        }

        return true;
    }
}
