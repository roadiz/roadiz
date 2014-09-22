<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file NodeTypeFieldSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for NodeTypeField.
 */
class NodeTypeFieldJsonSerializer extends AbstractJsonSerializer
{

    /**
     * Create a simple associative array with NodeTypeField
     * entity.
     *
     * @param RZ\Renzo\Core\Entities\NodeTypeField $nodeTypeField
     *
     * @return array
     */
    public static function toArray($nodeTypeField)
    {
        $data = array();

        $data['name'] =           $nodeTypeField->getName();
        $data['label'] =          $nodeTypeField->getLabel();
        $data['description'] =    $nodeTypeField->getDescription();
        $data['visible'] =        $nodeTypeField->isVisible();
        $data['type'] =           $nodeTypeField->getType();
        $data['indexed'] =        $nodeTypeField->isIndexed();
        $data['virtual'] =        $nodeTypeField->isVirtual();
        $data['default_values'] = $nodeTypeField->getDefaultValues();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return RZ\Renzo\Core\Entities\NodeTypeField
     */
    public static function deserialize($jsonString)
    {
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'name',
            'label',
            'description',
            'visible',
            'type',
            'indexed',
            'virtual',
            'default_values'
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));

        return $serializer->deserialize($jsonString, 'RZ\Renzo\Core\Entities\NodeTypeField', 'json');
    }
}
