<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file UrlAliasSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\UrlAlias;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Json Serialization handler for UrlAlias.
 */
class UrlAliasJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a UrlAlias.
     *
     * @param RZ\Renzo\Core\Entities\UrlAlias $urlAlias
     *
     * @return array
     */
    public static function toArray($urlAlias)
    {
        $data = array();

        $data['alias'] = $urlAlias->getAlias();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return RZ\Renzo\Core\Entities\UrlAlias
     */
    public static function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'alias'
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));

        return $serializer->deserialize($jsonString, 'RZ\Renzo\Core\Entities\UrlAlias', 'json');
    }
}
