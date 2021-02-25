<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterNodesSourcesEvent extends Event
{
    /**
     * @var NodesSources
     */
    protected $nodeSource;

    public function __construct(NodesSources $nodeSource)
    {
        $this->nodeSource = $nodeSource;
    }

    public function getNodeSource(): NodesSources
    {
        return $this->nodeSource;
    }
}
