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
class NodeTypeJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeType.
     *
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return array
     */
    public static function toArray($nodeType)
    {
        $data = array();

        $data['name'] =           $nodeType->getName();
        $data['displayName'] =    $nodeType->getDisplayName();
        $data['description'] =    $nodeType->getDescription();
        $data['visible'] =        $nodeType->isVisible();
        $data['newsletterType'] = $nodeType->isNewsletterType();
        $data['hidingNodes'] =    $nodeType->isHidingNodes();
        $data['fields'] =         array();

        foreach ($nodeType->getFields() as $nodeTypeField) {
            $nodeTypeFieldData = NodeTypeFieldJsonSerializer::toArray($nodeTypeField);

            $data['fields'][] = $nodeTypeFieldData;
        }

        return $data;
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
