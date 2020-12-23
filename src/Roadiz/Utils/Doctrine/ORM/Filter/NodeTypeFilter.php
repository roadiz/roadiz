<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderBuildEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesBuildEvent;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Filter on nodeType fields when criteria contains nodeType. prefix.
 *
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
class NodeTypeFilter implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderNodesSourcesBuildEvent::class => [['onNodesSourcesQueryBuilderBuild', 40]],
            QueryBuilderBuildEvent::class => [
                ['onNodeQueryBuilderBuild', 30],
            ]
        ];
    }

    protected function supports(QueryBuilderBuildEvent $event)
    {
        return $event->supports() && false !== strpos($event->getProperty(), 'nodeType.');
    }

    /**
     * @param QueryBuilderBuildEvent $event
     */
    public function onNodeQueryBuilderBuild(QueryBuilderBuildEvent $event)
    {
        if ($this->supports($event)) {
            // Prevent other query builder filters to execute
            $event->stopPropagation();
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            $qb = $event->getQueryBuilder();
            $baseKey = $simpleQB->getParameterKey($event->getProperty());

            if (!$simpleQB->joinExists(
                $simpleQB->getRootAlias(),
                EntityRepository::NODETYPE_ALIAS
            )
            ) {
                $qb->addSelect(EntityRepository::NODETYPE_ALIAS);
                $qb->innerJoin(
                    $simpleQB->getRootAlias() . '.nodeType',
                    EntityRepository::NODETYPE_ALIAS
                );
            }

            $prefix = EntityRepository::NODETYPE_ALIAS . '.';
            $key = str_replace('nodeType.', '', $event->getProperty());
            $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
        }
    }

    /**
     * @param QueryBuilderNodesSourcesBuildEvent $event
     */
    public function onNodesSourcesQueryBuilderBuild(QueryBuilderNodesSourcesBuildEvent $event)
    {
        if ($this->supports($event)) {
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            if (false !== strpos($event->getProperty(), 'node.nodeType.')) {
                // Prevent other query builder filters to execute
                $event->stopPropagation();
                $qb = $event->getQueryBuilder();
                $baseKey = $simpleQB->getParameterKey($event->getProperty());

                if (!$simpleQB->joinExists(
                    $simpleQB->getRootAlias(),
                    EntityRepository::NODE_ALIAS
                )
                ) {
                    $qb->innerJoin(
                        $simpleQB->getRootAlias() . '.node',
                        EntityRepository::NODE_ALIAS
                    );
                }
                if (!$simpleQB->joinExists(
                    $simpleQB->getRootAlias(),
                    EntityRepository::NODETYPE_ALIAS
                )
                ) {
                    $qb->addSelect(EntityRepository::NODETYPE_ALIAS);
                    $qb->innerJoin(
                        EntityRepository::NODE_ALIAS . '.nodeType',
                        EntityRepository::NODETYPE_ALIAS
                    );
                }

                $prefix = EntityRepository::NODETYPE_ALIAS . '.';
                $key = str_replace('node.nodeType.', '', $event->getProperty());
                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
            }
        }
    }
}
