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
class NodeTypeFieldJsonSerializer implements SerializerInterface
{

    /**
     * Serializes data.
     *
     * This method does not output a valid JSON string
     * but only a ready-to-encode array. This will be encoded
     * by the parent Node-type serialize method.
     *
     * @return array
     * @see RZ\Renzo\Core\Serializers\NodeTypeJsonSerializer::serialize
     */
    public static function serialize($nodeTypeField)
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

        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
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