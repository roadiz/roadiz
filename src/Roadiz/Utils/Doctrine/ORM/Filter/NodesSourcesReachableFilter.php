<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Events\FilterNodesSourcesQueryBuilderCriteriaEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesApplyEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesBuildEvent;
use RZ\Roadiz\Core\Events\QueryNodesSourcesEvent;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
final class NodesSourcesReachableFilter implements EventSubscriberInterface
{
    private NodeTypes $nodeTypesBag;

    const PARAMETER = [
        'node.nodeType.reachable',
        'reachable'
    ];

    /**
     * @param NodeTypes $nodeTypesBag
     */
    public function __construct(NodeTypes $nodeTypesBag)
    {
        $this->nodeTypesBag = $nodeTypesBag;
    }

    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderNodesSourcesBuildEvent::class => [['onNodesSourcesQueryBuilderBuild', 41]],
            QueryBuilderNodesSourcesApplyEvent::class => [['onNodesSourcesQueryBuilderApply', 41]],
            QueryNodesSourcesEvent::class => [['onQueryNodesSourcesEvent', 0]],
        ];
    }

    /**
     * @param FilterNodesSourcesQueryBuilderCriteriaEvent $event
     *
     * @return bool
     */
    protected function supports(FilterNodesSourcesQueryBuilderCriteriaEvent $event): bool
    {
        return $event->supports() &&
            in_array($event->getProperty(), static::PARAMETER) &&
            is_bool($event->getValue());
    }

    /**
     * @param QueryBuilderNodesSourcesBuildEvent $event
     */
    public function onNodesSourcesQueryBuilderBuild(QueryBuilderNodesSourcesBuildEvent $event)
    {
        if ($this->supports($event)) {
            // Prevent other query builder filters to execute
            $event->stopPropagation();
            $qb = $event->getQueryBuilder();
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            $value = (bool) $event->getValue();

            $nodeTypes = array_unique(array_filter($this->nodeTypesBag->all(), function (NodeType $nodeType) use ($value) {
                return $nodeType->getReachable() === $value;
            }));

            if (count($nodeTypes) > 0) {
                $orX = $qb->expr()->orX();
                /** @var NodeType $nodeType */
                foreach ($nodeTypes as $nodeType) {
                    $orX->add($qb->expr()->isInstanceOf(
                        $simpleQB->getRootAlias(),
                        $nodeType->getSourceEntityFullQualifiedClassName()
                    ));
                }
                $qb->andWhere($orX);
            }
        }
    }

    public function onNodesSourcesQueryBuilderApply(QueryBuilderNodesSourcesApplyEvent $event)
    {
        if ($this->supports($event)) {
            // Prevent other query builder filters to execute
            $event->stopPropagation();
        }
    }

    public function onQueryNodesSourcesEvent(QueryNodesSourcesEvent $event)
    {
        if ($event->supports()) {
            // TODO: Find a way to reduce NodeSource query joins when filtered by node-types.
        }
    }
}
