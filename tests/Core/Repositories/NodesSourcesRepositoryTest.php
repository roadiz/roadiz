<?php

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Repositories\NodesSourcesRepository;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

class NodesSourcesRepositoryTest extends DefaultThemeDependentCase
{
    /**
     * @return NodeRepository
     */
    protected function getNodeRepository()
    {
        return $this->get('em')->getRepository(Node::class);
    }

    /**
     * @return NodesSourcesRepository
     */
    protected function getNodesSourcesRepository()
    {
        return $this->get('em')->getRepository(NodesSources::class);
    }

    public function testFindByNodeName()
    {
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.nodeName' => 'home'
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByNodeTypeName()
    {
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.nodeType.name' => 'Neutral'
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByNodeType()
    {
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.nodeType' => $this->get('nodeTypesBag')->get('Neutral'),
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByANodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.aNodes.nodeA' => $home,
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByBNodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.bNodes.nodeB' => $home,
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByANodesFieldName()
    {
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.aNodes.field.name' => 'related_node',
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByBNodesFieldName()
    {
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.bNodes.field.name' => 'related_node',
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByANodesAndFieldName()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.aNodes.nodeA' => $home,
            'node.aNodes.field.name' => 'related_node',
        ]);
        $this->assertNotEmpty($result);
    }

    public function testFindByBNodesAndFieldName()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $result = $this->getNodesSourcesRepository()->findBy([
            'node.bNodes.nodeB' => $home,
            'node.bNodes.field.name' => 'related_node',
        ]);
        $this->assertNotEmpty($result);
    }
}
