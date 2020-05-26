<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Entities\NodesSources;

interface NodesSourcesPathAggregator
{
    public function aggregatePath(NodesSources $nodesSources): string;
}
