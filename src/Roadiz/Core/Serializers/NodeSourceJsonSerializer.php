<?php
/**
 * Copyright © 2014, REZO ZERO
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file NodeSourceJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\NodeType;

use Symfony\Component\Serializer\Serializer;
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
     * @param RZ\Roadiz\Core\Entities\NodeSource $nodeSource
     *
     * @return array
     */
    public static function toArray($nodeSource)
    {
        $data = array();

        $data['translation'] = $nodeSource->getTranslation()->getLocale();
        $data['title'] = $nodeSource->getTitle();
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
     * @param RZ\Roadiz\Core\Entities\NodeType $type
     *
     * @return RZ\Roadiz\Core\Entities\NodeSource
     */
    public static function deserializeWithNodeType($string, NodeType $type)
    {
        $fields = $type->getFields();
        /*
         * Create source default values
         */
        $sourceDefaults = array(
            "title",
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
