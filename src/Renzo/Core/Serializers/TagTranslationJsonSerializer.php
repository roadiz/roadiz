<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file NodeSourceSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\NodeSource;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Json Serialization handler for NodeSource.
 */
class TagTranslationJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param RZ\Renzo\Core\Entities\NodeSource $nodeSource
     *
     * @return array
     */
    public static function toArray($tt)
    {
        $data = array();

        $data['translation'] = $tt->getTranslation()->getLocale();
        $data['title'] = $tt->getname();
        $data['description'] = $tt->getDescription();


        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @see NodeSourceJsonSerializer::deserializeWithNodeType
     */
    public static function deserialize($string)
    {
        throw new \RuntimeException(
            "Cannot simply deserialize a NodesSources entity. ".
            "Use 'deserializeWithNodeType' method instead.",
            1
        );
    }
}
