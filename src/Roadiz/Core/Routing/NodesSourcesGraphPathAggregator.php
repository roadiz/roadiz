<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Entities\NodesSources;

class NodesSourcesGraphPathAggregator implements NodesSourcesPathAggregator
{
    public function aggregatePath(NodesSources $nodesSources): string
    {
        $urlTokens[] = $nodesSources->getIdentifier();

        $parent = $nodesSources->getParent();
        if ($parent !== null && !$parent->getNode()->isHome()) {
            do {
                if ($parent->getNode()->isVisible()) {
                    $urlTokens[] = $parent->getIdentifier();
                }
                $parent = $parent->getParent();
            } while ($parent !== null && !$parent->getNode()->isHome());
        }

        return implode('/', array_reverse($urlTokens));
    }
}
