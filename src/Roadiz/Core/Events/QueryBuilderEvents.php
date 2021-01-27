<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderApplyEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderBuildEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderSelectEvent;

/**
 * @package RZ\Roadiz\Core\Events
 * @deprecated
 */
final class QueryBuilderEvents
{
    /**
     * @deprecated
     */
    const QUERY_BUILDER_SELECT = QueryBuilderSelectEvent::class;
    /**
     * @deprecated
     */
    const QUERY_BUILDER_BUILD_FILTER = QueryBuilderBuildEvent::class;
    /**
     * @deprecated
     */
    const QUERY_BUILDER_APPLY_FILTER = QueryBuilderApplyEvent::class;
}
