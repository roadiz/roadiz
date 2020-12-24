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

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Tests\SchemaDependentCase;

class NodeTypeHandlerTest extends SchemaDependentCase
{
    /**
     * @dataProvider serializeToJsonProvider
     * @param $sourceNodeType
     * @param $expectedFile
     */
    public function testSerializeToJson($sourceNodeType, $expectedFile)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $json = $serializer->serialize(
            $sourceNodeType,
            'json',
            SerializationContext::create()->setGroups(['node_type', 'position'])
        );

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

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $nt = $serializer->deserialize(
            $expectedJson,
            NodeType::class,
            'json'
        );
        $this->assertEquals(get_class($nt), NodeType::class);

        $newJson = $serializer->serialize(
            $nt,
            'json',
            SerializationContext::create()->setGroups(['node_type', 'position'])
        );
        $this->assertJsonStringEqualsJsonString($expectedJson, $newJson);
    }

    /**
     * @dataProvider defaultValueTestProvider
     * @param $json
     * @param $expectedValue
     */
    public function testDefaultValue($json, $expectedValue)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $nt = $serializer->deserialize(
            file_get_contents($json),
            NodeType::class,
            'json'
        );

        $ntfields = $nt->getFields();
        if (count($ntfields) > 0) {
            $this->assertEquals($expectedValue, $ntfields[0]->getDefaultValues());
        } else {
            $this->markTestSkipped('No field found');
        }
    }

    public function serializeToJsonProvider()
    {
        // Node type #1
        $nt1 = new NodeType();
        $nt1->setName('page type');
        $nt1->setDisplayName('Page Type');

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

        return [
            [new NodeType(), ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json'],
            [$nt1, ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler02.json'],
            [$nt2, ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json'],
        ];
    }

    public function deserializeProvider()
    {
        return [
            [ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json'],
            [ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler02.json'],
            [ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json'],
        ];
    }

    public function defaultValueTestProvider()
    {
        return [
            [ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json', null],
            [ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json', "value1, value2"],
        ];
    }
}
