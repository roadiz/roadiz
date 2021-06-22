<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Indexer;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;

final class NodeIndexer extends NodesSourcesIndexer
{
    public function index($id): void
    {
        $node = $this->entityManager->find(Node::class, $id);
        if (null !== $node) {
            $update = $this->getSolr()->createUpdate();
            /** @var NodesSources $nodeSource */
            foreach ($node->getNodeSources() as $nodeSource) {
                $this->indexNodeSource($nodeSource, $update);
            }
            $update->addCommit(true, true, false);
            $this->getSolr()->update($update);
        }
    }

    public function delete($id): void
    {
        $node = $this->entityManager->find(Node::class, $id);
        if (null !== $node) {
            foreach ($node->getNodeSources() as $nodeSource) {
                $this->deleteNodeSource($nodeSource);
            }

            // optimize the index
            $this->commitSolr();
        }
    }
}
