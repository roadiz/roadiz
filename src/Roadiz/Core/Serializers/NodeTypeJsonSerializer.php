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
 * @file NodeTypeJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Json Serialization handler for NodeType.
 */
class NodeTypeJsonSerializer extends AbstractJsonSerializer
{
    protected $ntfSerializer;

    public function __construct()
    {
        $this->ntfSerializer = new NodeTypeFieldJsonSerializer();
    }
    /**
     * Create a simple associative array with a NodeType.
     *
     * @param NodeType $nodeType
     *
     * @return array
     */
    public function toArray($nodeType)
    {
        $data = [];

        $data['name'] = $nodeType->getName();
        $data['displayName'] = $nodeType->getDisplayName();
        $data['description'] = $nodeType->getDescription();
        $data['visible'] = $nodeType->isVisible();
        $data['newsletterType'] = $nodeType->isNewsletterType();
        $data['hidingNodes'] = $nodeType->isHidingNodes();
        $data['color'] = $nodeType->getColor();
        $data['defaultTtl'] = $nodeType->getDefaultTtl();
        $data['reachable'] = $nodeType->isReachable();
        $data['publishable'] = $nodeType->isPublishable();
        $data['fields'] = [];

        foreach ($nodeType->getFields() as $nodeTypeField) {
            $nodeTypeFieldData = $this->ntfSerializer->toArray($nodeTypeField);
            $data['fields'][] = $nodeTypeFieldData;
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return \RZ\Roadiz\Core\Entities\NodeType
     */
    public function deserialize($string)
    {
        $encoder = new JsonEncoder();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter([
            'name',
            'displayName',
            'description',
            'visible',
            'newsletterType',
            'defaultTtl',
            'color',
            'hidingNodes',
            'reachable',
            'publishable',
        ]);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);
        $serializer = new Serializer([$normalizer], [$encoder]);
        $nodeType = $serializer->deserialize($string, NodeType::class, 'json');

        /*
         * Importing Fields.
         *
         * We need to extract fields from node-type and to re-encode them
         * to pass to NodeTypeFieldJsonSerializer.
         */
        $tempArray = json_decode($string, true);

        foreach ($tempArray['fields'] as $fieldAssoc) {
            $ntField = $this->ntfSerializer->deserialize(json_encode($fieldAssoc));
            $nodeType->addField($ntField);
        }

        return $nodeType;
    }
}
