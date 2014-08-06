<?php

use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;

class NodeTypeHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider serializeToJsonProvider
     */
    public function testSerializeToJson($sourceNodeType, $expected) {
        $json = $sourceNodeType->getHandler()->serializeToJson();
        // Assert
        $this->assertEquals($json, $expected);
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
            array(new NodeType(), '{"node_type":{"name":null,"displayName":null,"description":null,"visible":true,"newsletterType":false,"hidingNodes":false,"fields":[]}}'),
            array($nt1, '{"node_type":{"name":"PageType","displayName":null,"description":null,"visible":true,"newsletterType":false,"hidingNodes":false,"fields":[]}}'),
            array($nt2, '{"node_type":{"name":"BlogPost","displayName":null,"description":null,"visible":true,"newsletterType":false,"hidingNodes":false,"fields":[]}}')
        );
    }
}

