<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderBuildEvent;

/**
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterNodesSourcesQueryBuilderCriteriaEvent extends QueryBuilderBuildEvent
{
    /**
     * @inheritDoc
     */
    public function __construct(QueryBuilder $queryBuilder, $property, $value, $actualEntityName)
    {
        parent::__construct($queryBuilder, NodesSources::class, $property, $value, $actualEntityName);
    }

    /**
     * @inheritDoc
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
