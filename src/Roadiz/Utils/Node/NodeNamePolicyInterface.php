<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use RZ\Roadiz\Core\Entities\NodesSources;

interface NodeNamePolicyInterface
{
    /**
     * @param NodesSources $nodeSource
     * @return string Return a canonical node' name built against a NS title and node-type.
     */
    public function getCanonicalNodeName(NodesSources $nodeSource): string;

    /**
     * @param NodesSources $nodeSource
     * @return string Return a canonical node' name built against a NS title, node-type and a unique suffix.
     */
    public function getSafeNodeName(NodesSources $nodeSource): string;

    /**
     * @param NodesSources $nodeSource
     * @return string Return a canonical node' name built against a NS title, node-type and a date suffix.
     */
    public function getDatestampedNodeName(NodesSources $nodeSource): string;

    public function isNodeNameWithUniqId(string $canonicalNodeName, string $nodeName): bool;

    public function isNodeNameAlreadyUsed(string $nodeName): bool;

    public function isNodeNameValid(string $nodeName): bool;
}
