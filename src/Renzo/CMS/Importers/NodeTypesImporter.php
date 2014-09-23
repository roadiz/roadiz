<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypesImporter.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Importers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\NodeTypeJsonSerializer;
use RZ\Renzo\Core\Serializers\NodeTypeFieldJsonSerializer;

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
class NodeTypesImporter implements ImporterInterface
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
        $nodeType = NodeTypeJsonSerializer::deserialize($serializedData);
        $existingNodeType = Kernel::getInstance()->em()
                 ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                 ->findOneByName($nodeType->getName());
        if ($existingNodeType === null) {
            Kernel::getInstance()->em()->persist($nodeType);
            $existingNodeType = $nodeType;
            foreach ($nodeType->getFields() as $field) {
                Kernel::getInstance()->em()->persist($field);
                $field->setNodeType($nodeType);
            }
        } else {
            $existingNodeType->getHandler()->diff($nodeType);
        }
        Kernel::getInstance()->em()->flush();
        return $return;
    }
}
