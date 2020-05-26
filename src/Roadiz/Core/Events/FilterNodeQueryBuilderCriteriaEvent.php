<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderBuildEvent;

/**
 * Class FilterNodeQueryBuilderCriteriaEvent
 *
 * @package RZ\Roadiz\Core\Events
 * @deprecated
 */
class FilterNodeQueryBuilderCriteriaEvent extends QueryBuilderBuildEvent
{
    /**
     * @inheritDoc
     */
    public function __construct(QueryBuilder $queryBuilder, $property, $value, $actualEntityName)
    {
        parent::__construct($queryBuilder, Node::class, $property, $value, $actualEntityName);
    }

    /**
     * @inheritDoc
     */
    public function supports(): bool
    {
        if ($this->actualEntityName === Node::class) {
            return true;
        }

        return false;
    }
}
