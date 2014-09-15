<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file GroupsImporter.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Importers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Setting;
use RZ\Renzo\Core\Entities\SettingGroup;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\GroupJsonSerializer;
use RZ\Renzo\Core\Serializers\GroupCollectionJsonSerializer;

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
class GroupsImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing group.
     *
     * @param Array $serialized
     *
     * @return bool
     */
    public static function importJsonFile($serializedData)
    {
        $return = false;
        $groups = GroupCollectionJsonSerializer::deserialize($serializedData);
        foreach ($groups as $group) {
            $existingGroup = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Group')
                ->findOneBy(array('name'=>$group->getName()));

            if (null === $existingGroup) {
                foreach ($group->getRolesEntities() as $role) {
                  /*
                   * then persist each role
                   */
                    $role = Kernel::getInstance()->em()->getRepository('RZ\Renzo\Core\Entities\Role')->findOneByName($role->getName());
                }

                Kernel::getInstance()->em()->persist($group);
                // Flush before creating group's roles.
                Kernel::getInstance()->flush();
            } else {
                $existingGroup->getHandler()->diff($group);
            }

            Kernel::getInstance()->em()->flush();
        }
        $return = true;
        return $return;
    }

}