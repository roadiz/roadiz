<?php

use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Serializers\NodeTypeJsonSerializer;

class NodeTypeHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider serializeToJsonProvider
     */
    public function testSerializeToJson($sourceNodeType, $expectedFile)
    {
        $serializer = new NodeTypeJsonSerializer($sourceNodeType);
        $json = $serializer->serialize();

        // Assert
        $this->assertJsonStringEqualsJsonFile($expectedFile, $json);
    }

    public function serializeToJsonProvider()
    {
        // Node type #1
        $nt1 = new NodeType();
        $nt1->setName('page type');

        // Node type #2
        $nt2 = new NodeType();
        $nt2->setName('blog post');
        $nt2->setDisplayName('Un blog post');

        $ntf1 = new NodeTypeField();
        $ntf1->setName('Title');
        $ntf1->setType(NodeTypeField::MARKDOWN_T);

        $nt2->addField($ntf1);

        return array(
            array(new NodeType(), RENZO_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json'),
            array($nt1, RENZO_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler02.json'),
            array($nt2, RENZO_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json')
        );
    }
}

