<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file NodeTypeSerializer.php
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
 * Json Serialization handler for NodeType.
 */
class NodeTypeJsonSerializer implements SerializerInterface
{


    /**
     * Serializes data into Json.
     *
     * @return string
     */
    public function serialize($nodeType)
    {
        $data = array();

        $data['name'] =           $nodeType->getName();
        $data['displayName'] =    $nodeType->getDisplayName();
        $data['description'] =    $nodeType->getDescription();
        $data['visible'] =        $nodeType->isVisible();
        $data['newsletterType'] = $nodeType->isNewsletterType();
        $data['hidingNodes'] =    $nodeType->isHidingNodes();
        $data['fields'] =         array();

        foreach ($nodeType()->getFields() as $nodeTypeField) {
            $nodeTypeFieldData = array();
            $nodeTypeFieldData['name'] =           $nodeTypeField->getName();
            $nodeTypeFieldData['label'] =          $nodeTypeField->getLabel();
            $nodeTypeFieldData['description'] =    $nodeTypeField->getDescription();
            $nodeTypeFieldData['visible'] =        $nodeTypeField->isVisible();
            $nodeTypeFieldData['type'] =           $nodeTypeField->getType();
            $nodeTypeFieldData['indexed'] =        $nodeTypeField->isIndexed();
            $nodeTypeFieldData['virtual'] =        $nodeTypeField->isVirtual();
            $nodeTypeFieldData['default_values'] = $nodeTypeField->getDefaultValues();

            $data['fields'][] = $nodeTypeFieldData;
        }

        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return RZ\Renzo\Core\Entities\NodeType
     */
    public static function deserialize($string)
    {
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'name',
            'displayName',
            'display_name',
            'description',
            'visible',
            'newsletterType',
            'hidingNodes'
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));
        $nodeType = $serializer->deserialize($string, 'RZ\Renzo\Core\Entities\NodeType', 'json');

        /*
         * Importing Fields.
         *
         * We need to extract fields from node-type and to re-encode them
         * to pass to NodeTypeFieldJsonSerializer.
         */
        $tempArray = json_decode($string, true);

        foreach ($tempArray['fields'] as $fieldAssoc) {
            $ntField = NodeTypeFieldJsonSerializer::deserialize(json_encode($fieldAssoc));
            $nodeType->addField($ntField);
        }

        return $nodeType;
    }
}