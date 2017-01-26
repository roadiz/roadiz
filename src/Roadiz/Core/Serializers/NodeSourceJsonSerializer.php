<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeSourceJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Json Serialization handler for NodeSource.
 */
class NodeSourceJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param \RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     *
     * @return array
     */
    public function toArray($nodeSource)
    {
        $urlAliasSerializer = new UrlAliasJsonSerializer();
        $data = [];

        $data['translation'] = $nodeSource->getTranslation()->getLocale();
        $data['title'] = $nodeSource->getTitle();
        $data['meta_title'] = $nodeSource->getMetaTitle();
        $data['meta_keywords'] = $nodeSource->getMetaKeywords();
        $data['meta_description'] = $nodeSource->getMetaDescription();

        $data = array_merge($data, $this->getSourceFields($nodeSource));

        $data['url_aliases'] = [];

        foreach ($nodeSource->getUrlAliases() as $alias) {
            $data['url_aliases'][] = $urlAliasSerializer->toArray($alias);
        }

        return $data;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     *
     * @return array
     */
    protected function getSourceFields($nodeSource)
    {
        $fields = $nodeSource->getNode()->getNodeType()->getFields();

        /*
         * Create nodeSource default values
         */
        $sourceDefaults = [];
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
     */
    public function deserialize($string)
    {
        throw new \RuntimeException(
            "Cannot simply deserialize a NodesSources entity. " .
            "Use 'deserializeWithNodeType' method instead.",
            1
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param string                          $string
     * @param \RZ\Roadiz\Core\Entities\NodeType $type
     *
     * @return \RZ\Roadiz\Core\Entities\NodesSources
     */
    public function deserializeWithNodeType($string, NodeType $type)
    {
        $fields = $type->getFields();
        /*
         * Create source default values
         */
        $sourceDefaults = [
            "title",
            "meta_title",
            "meta_keywords",
            "meta_description",
        ];

        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $sourceDefaults[] = $field->getName();
            }
        }

        $encoder = new JsonEncoder();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter($sourceDefaults);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);

        $serializer = new Serializer([$normalizer], [$encoder]);
        $node = $serializer->deserialize(
            $string,
            NodeType::getGeneratedEntitiesNamespace() . '\\' . $type->getSourceEntityClassName(),
            'json'
        );

        return $node;
    }
}
