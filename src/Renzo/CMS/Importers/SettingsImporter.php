<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file SettingsImporter.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Importers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Setting;
use RZ\Renzo\Core\Entities\SettingGroup;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\SettingJsonSerializer;
use RZ\Renzo\Core\Serializers\SettingCollectionJsonSerializer;

use RZ\Renzo\CMS\Importers\ImporterInterface;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class SettingsImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param string $serializedData
     *
     * @return bool
     */
    public static function importJsonFile($serializedData)
    {
        $return = false;
        $settingGroups = SettingCollectionJsonSerializer::deserialize($serializedData);

        $groupsNames = Kernel::getInstance()->em()
                  ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
                  ->findAllNames();

        $settingsNames = Kernel::getInstance()->em()
                  ->getRepository('RZ\Renzo\Core\Entities\Setting')
                  ->findAllNames();

        $newSettings = new ArrayCollection();

        foreach ($settingGroups as $index => $settingGroup) {

            /*
             * Loop over settings to set their group
             * and move them to a temp collection
             */
            foreach ($settingGroup->getSettings() as $setting) {

                if (!in_array($setting->getName(), $settingsNames)) {
                    //Kernel::getInstance()->em()->persist($setting);
                } else {
                    $setting = Kernel::getInstance()->em()
                        ->getRepository('RZ\Renzo\Core\Entities\Setting')
                        ->findOneByName($setting->getName());

                }
                if ($settingGroup->getName() == "__default__") {
                    $setting->setSettingGroup(null);
                } else {
                    $setting->setSettingGroup($settingGroup);
                }
                $newSettings->add($setting);
            }
        }

        foreach ($newSettings as $setting) {

            $settingGroup = $setting->getSettingGroup();

            /*
             * Persist or not group
             */
            if (null !== $settingGroup) {

                if (!in_array($settingGroup->getName(), $groupsNames)) {
                    Kernel::getInstance()->em()->persist($settingGroup);
                } else {
                    $settingGroup = Kernel::getInstance()->em()
                        ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
                        ->findOneByName($settingGroup->getName());

                    $setting->setSettingGroup($settingGroup);
                }
            }

            if ($setting->getId() === null) {
                Kernel::getInstance()->em()->persist($setting);
            }
        }
        Kernel::getInstance()->em()->flush();

        $return = true;

        return $return;
    }
}
