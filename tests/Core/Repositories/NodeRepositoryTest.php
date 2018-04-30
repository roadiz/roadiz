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
        $this->getNodeRepository()->findBy([
            'nodeType.name' => 'Neutral'
        ]);
    }

    public function testFindByNodeType()
    {
        $this->getNodeRepository()->findBy([
            'nodeType' => $this->get('nodeTypesBag')->get('Neutral'),
        ]);
    }

    public function testFindByANodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $this->getNodeRepository()->findBy([
            'aNodes.nodeA' => $home,
        ]);
    }

    public function testFindByBNodes()
    {
        $home = $this->getNodeRepository()->findHomeWithDefaultTranslation();
        $this->getNodeRepository()->findBy([
            'bNodes.nodeB' => $home,
        ]);
    }

    public function testFindByANodesFieldName()
    {
        $this->getNodeRepository()->findBy([
            'aNodes.field.name' => 'related_node',
        ]);
    }

    public function testFindByBNodesFieldName()
    {
        $this->getNodeRepository()->findBy([
            'bNodes.field.name' => 'related_node',
        ]);
    }
}
