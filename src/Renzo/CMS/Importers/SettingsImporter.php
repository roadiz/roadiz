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
        $groups = Kernel::getInstance()->em()
                  ->getRepository('RZ\Renzo\Core\Entities\SettingGroup')
                  ->findAll();
        $settings = Kernel::getInstance()->em()
                  ->getRepository('RZ\Renzo\Core\Entities\Setting')
                  ->findAll();
        $groups = new ArrayCollection($groups);
        foreach ($settingGroups as $group) {
            if ($group->getName() != "__default__") {
                $baseGroup = null;
                foreach ($groups as $existingGroup) {
                    if ($group->getName() == $existingGroup->getName()) {
                        $baseGroup = $existingGroup;
                        break ;
                    }
                }
                if ($baseGroup === null) {
                    Kernel::getInstance()->em()->persist($group);
                    Kernel::getInstance()->em()->flush();
                    $baseGroup = $group;
                }
            } else {
                $baseGroup = null;
            }
            foreach ($group->getSettings() as $setting) {
                $baseEntry = null;
                foreach ($settings as $existingSetting) {
                    if ($setting->getName() == $existingSetting->getName()) {
                        $baseEntry = $existingSetting;
                        break ;
                    }
                }
                if ($baseEntry === null) {
                    Kernel::getInstance()->em()->persist($setting);
                    Kernel::getInstance()->em()->flush();
                    if ($baseGroup != null) {
                        $baseGroup->addSetting($baseEntry);
                    }
                } else {
                    $baseEntry->setType($setting->getType());
                    $baseEntry->setValue($setting->getValue());
                    $baseEntry->setVisible($setting->isVisible());
                    if ($baseGroup !== null) {
                        $baseEntry->setSettingGroup($baseGroup);
                    } else {
                        $baseEntry->setSettingGroup(null);
                    }
                }
            }
            Kernel::getInstance()->em()->flush();

        }
        $return = true;
        return $return;
    }

}
