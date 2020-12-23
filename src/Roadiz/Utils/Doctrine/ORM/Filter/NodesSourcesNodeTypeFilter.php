<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Events\FilterNodesSourcesQueryBuilderCriteriaEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesApplyEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesBuildEvent;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
final class NodesSourcesNodeTypeFilter implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderNodesSourcesBuildEvent::class => [['onNodesSourcesQueryBuilderBuild', -9]],
            QueryBuilderNodesSourcesApplyEvent::class => [['onNodesSourcesQueryBuilderApply', -9]],
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
            $event->getProperty() === 'node.nodeType' &&
            (
                $event->getValue() instanceof NodeType ||
                (is_array($event->getValue()) && $event->getValue()[0] instanceof NodeType)
            );
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
            $value = $event->getValue();

            if ($value instanceof NodeType) {
                $qb->andWhere($qb->expr()->isInstanceOf(
                    $simpleQB->getRootAlias(),
                    $value->getSourceEntityFullQualifiedClassName()
                ));
            } elseif (is_array($value)) {
                $nodeTypes = [];
                foreach ($value as $nodeType) {
                    if ($nodeType instanceof NodeType) {
                        $nodeTypes[] = $nodeType;
                    }
                }
                $nodeTypes = array_unique($nodeTypes);

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
    }

    public function onNodesSourcesQueryBuilderApply(QueryBuilderNodesSourcesApplyEvent $event)
    {
        if ($this->supports($event)) {
            // Prevent other query builder filters to execute
            $event->stopPropagation();
        }
    }
}
