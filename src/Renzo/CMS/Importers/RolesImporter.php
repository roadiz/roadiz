<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file RolesImporter.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Importers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Setting;
use RZ\Renzo\Core\Entities\SettingGroup;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\RoleJsonSerializer;
use RZ\Renzo\Core\Serializers\RoleCollectionJsonSerializer;

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
class RolesImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param Array $serialized
     *
     * @return bool
     */
    public static function importJsonFile($serializedData)
    {
        $return = true;
        $roles = RoleCollectionJsonSerializer::deserialize($serializedData);
        return $return;
    }

}