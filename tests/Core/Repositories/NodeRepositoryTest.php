<?php

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

class NodeRepositoryTest extends DefaultThemeDependentCase
{
    /**
     * @return NodeRepository
     */
    protected function getNodeRepository()
    {
        return $this->get('em')->getRepository(Node::class);
    }

    public function testFindByNodeTypeName()
    {
        $result = $this->getNodeRepository()->findBy([
            'nodeType.name' => 'Neutral'
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByNodeType()
    {
        $result = $this->getNodeRepository()->findBy([
            'nodeType' => $this->get('nodeTypesBag')->get('Neutral'),
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByANodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $result = $this->getNodeRepository()->findBy([
            'aNodes.nodeA' => $home,
        ]);
        $this->assertEmpty($result);
    }

    public function testFindByBNodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $result = $this->getNodeRepository()->findBy([
            'bNodes.nodeB' => $home,
        ]);
        $this->assertEmpty($result);
    }

    public function testFindByANodesFieldName()
    {
        $result = $this->getNodeRepository()->findBy([
            'aNodes.field.name' => 'related_node',
        ]);
        $this->assertEmpty($result);
    }

    public function testFindByBNodesFieldName()
    {
        $result = $this->getNodeRepository()->findBy([
            'bNodes.field.name' => 'related_node',
        ]);
        $this->assertEmpty($result);
    }
}
