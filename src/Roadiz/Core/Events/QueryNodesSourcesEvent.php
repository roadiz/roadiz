<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\Query;
use RZ\Roadiz\Core\Entities\NodesSources;

final class QueryNodesSourcesEvent extends QueryEvent
{
    /**
     * @var string
     */
    protected $actualEntityName;

    /**
     * @param Query  $query
     * @param string $actualEntityName
     */
    public function __construct(Query $query, string $actualEntityName)
    {
        parent::__construct($query, NodesSources::class);
        $this->actualEntityName = $actualEntityName;
    }

    /**
     * @return string
     */
    public function getActualEntityName(): string
    {
        return $this->actualEntityName;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    public function supports(): bool
    {
        if ($this->actualEntityName === NodesSources::class) {
            return true;
        }

        $reflectionClass = new \ReflectionClass($this->actualEntityName);
        if ($reflectionClass->isSubclassOf(NodesSources::class)) {
            return true;
        }

        return false;
    }
}
