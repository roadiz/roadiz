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
class NodeSourceJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param RZ\Renzo\Core\Entities\NodeSource $nodeSource
     *
     * @return array
     */
    public static function toArray($nodeSource)
    {
        $data = array();

        $data['translation'] = $nodeSource->getTranslation()->getLocale();
        $data['meta_title'] = $nodeSource->getMetaTitle();
        $data['meta_keywords'] = $nodeSource->getMetaKeywords();
        $data['meta_description'] = $nodeSource->getMetaDescription();

        $data = array_merge($data, static::getSourceFields($nodeSource));

        $data['url_aliases'] = array();

        foreach ($nodeSource->getUrlAliases() as $alias) {
            $data['url_aliases'][] = UrlAliasJsonSerializer::toArray($alias);
        }

        return $data;
    }


    protected static function getSourceFields($nodeSource)
    {
        $fields = $nodeSource->getNode()->getNodeType()->getFields();

        /*
         * Create nodeSource default values
         */
        $sourceDefaults = array();
        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $getter = $field->getGetterName();
                $sourceDefaults[$field->getName()] = $nodeSource->$getter();
            }
        }

        return $sourceDefaults;
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

    /**
     * {@inheritDoc}
     *
     * @param string                          $string
     * @param RZ\Renzo\Core\Entities\NodeType $type
     *
     * @return RZ\Renzo\Core\Entities\NodeSource
     * @todo Need to deserialize from an array instead of Json string (too greedy).
     * Then need to link to existing translation.
     */
    public static function deserializeWithNodeType($string, NodeType $type)
    {
        $fields = $type->getFields();
        /*
         * Create source default values
         */
        $sourceDefaults = array(
            "meta_title",
            "meta_keywords",
            "meta_description"
        );

        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $sourceDefaults[] = $field->getName();
            }
        }

        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes($sourceDefaults);

        $serializer = new Serializer(array($normalizer), array($encoder));
        $node = $serializer->deserialize(
            $string,
            NodeType::getGeneratedEntitiesNamespace().'\\'.$type->getSourceEntityClassName(),
            'json'
        );

        return $node;
    }
}
