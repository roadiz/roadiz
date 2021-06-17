<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Indexer;

use RZ\Roadiz\Core\Entities\Node;

final class NodeIndexer extends NodesSourcesIndexer
{
    public function index($id): void
    {
        $node = $this->entityManager->find(Node::class, $id);
        if (null !== $node) {
            foreach ($node->getNodeSources() as $nodeSource) {
                $this->indexNodeSource($nodeSource);
            }
        }
    }

    public function delete($id): void
    {
        $node = $this->entityManager->find(Node::class, $id);
        if (null !== $node) {
            foreach ($node->getNodeSources() as $nodeSource) {
                $this->deleteNodeSource($nodeSource);
            }
        }
    }
}
