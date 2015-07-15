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
 * @file NodeTypeFieldJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Serialization class for NodeTypeField.
 */
class NodeTypeFieldJsonSerializer extends AbstractJsonSerializer
{

    /**
     * Create a simple associative array with NodeTypeField
     * entity.
     *
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $nodeTypeField
     *
     * @return array
     */
    public function toArray($nodeTypeField)
    {
        $data = [];

        $data['name'] = $nodeTypeField->getName();
        $data['label'] = $nodeTypeField->getLabel();
        $data['description'] = $nodeTypeField->getDescription();
        $data['visible'] = $nodeTypeField->isVisible();
        $data['type'] = $nodeTypeField->getType();
        $data['indexed'] = $nodeTypeField->isIndexed();
        $data['virtual'] = $nodeTypeField->isVirtual();
        $data['default_values'] = $nodeTypeField->getDefaultValues();
        $data['group_name'] = $nodeTypeField->getGroupName();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return RZ\Roadiz\Core\Entities\NodeTypeField
     */
    public function deserialize($jsonString)
    {
        $encoder = new JsonEncoder();

        $nameConverter = new CamelCaseToSnakeCaseNameConverter([
            'name',
            'label',
            'description',
            'visible',
            'type',
            'indexed',
            'virtual',
            'default_values',
            'group_name',
        ]);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);

        $serializer = new Serializer([$normalizer], [$encoder]);

        return $serializer->deserialize($jsonString, 'RZ\Roadiz\Core\Entities\NodeTypeField', 'json');
    }
}
