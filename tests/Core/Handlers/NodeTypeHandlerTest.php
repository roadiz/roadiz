<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodeTypeHandlerTest.php
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Serializers\NodeTypeJsonSerializer;

class NodeTypeHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider serializeToJsonProvider
     * @param $sourceNodeType
     * @param $expectedFile
     */
    public function testSerializeToJson($sourceNodeType, $expectedFile)
    {
        $serializer = new NodeTypeJsonSerializer();
        $json = $serializer->serialize($sourceNodeType);

        // Assert
        $this->assertJsonStringEqualsJsonFile($expectedFile, $json);
    }

    /**
     * @dataProvider deserializeProvider
     * @param $expectedFile
     */
    public function testDeserialize($expectedFile)
    {
        $expectedJson = file_get_contents($expectedFile);
        $serializer = new NodeTypeJsonSerializer();
        $nt = $serializer->deserialize($expectedJson);

        $newJson = $serializer->serialize($nt);
        $this->assertJsonStringEqualsJsonString($expectedJson, $newJson);
    }

    /**
     * @dataProvider defaultValueTestProvider
     * @param $json
     * @param $expectedValue
     */
    public function testDefaultValue($json, $expectedValue)
    {
        $serializer = new NodeTypeJsonSerializer();
        $nt = $serializer->deserialize(file_get_contents($json));

        $ntfields = $nt->getFields();
        if (count($ntfields) > 0) {
            $this->assertEquals($expectedValue, $ntfields[0]->getDefaultValues());
        }
    }

    public function serializeToJsonProvider()
    {
        // Node type #1
        $nt1 = new NodeType();
        $nt1->setName('page type');

        // Node type #2
        $nt2 = new NodeType();
        $nt2->setName('blog post');
        $nt2->setColor('#FF0000');
        $nt2->setDisplayName('Un blog post');
        $nt2->setPublishable(true);

        $ntf1 = new NodeTypeField();
        $ntf1->setName('Title');
        $ntf1->setType(NodeTypeField::MARKDOWN_T);
        $ntf1->setDefaultValues('value1, value2');
        $ntf1->setUniversal(false);
        $nt2->addField($ntf1);

        return array(
            array(new NodeType(), ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json'),
            array($nt1, ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler02.json'),
            array($nt2, ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json'),
        );
    }

    public function deserializeProvider()
    {
        return array(
            array(ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json'),
            array(ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler02.json'),
            array(ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json'),
        );
    }

    public function defaultValueTestProvider()
    {
        return array(
            array(ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json', null),
            array(ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json', "value1, value2"),
        );
    }
}
