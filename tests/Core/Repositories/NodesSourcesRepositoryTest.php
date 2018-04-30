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
        $this->getNodesSourcesRepository()->findBy([
            'node.nodeName' => 'home'
        ]);
    }

    public function testFindByNodeTypeName()
    {
        $this->getNodesSourcesRepository()->findBy([
            'node.nodeType.name' => 'Neutral'
        ]);
    }

    public function testFindByNodeType()
    {
        $this->getNodesSourcesRepository()->findBy([
            'node.nodeType' => $this->get('nodeTypesBag')->get('Neutral'),
        ]);
    }

    public function testFindByANodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $this->getNodesSourcesRepository()->findBy([
            'node.aNodes.nodeA' => $home,
        ]);
    }

    public function testFindByBNodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $this->getNodesSourcesRepository()->findBy([
            'node.bNodes.nodeB' => $home,
        ]);
    }

    public function testFindByANodesFieldName()
    {
        $this->getNodesSourcesRepository()->findBy([
            'node.aNodes.field.name' => 'related_node',
        ]);
    }

    public function testFindByBNodesFieldName()
    {
        $this->getNodesSourcesRepository()->findBy([
            'node.bNodes.field.name' => 'related_node',
        ]);
    }
}
