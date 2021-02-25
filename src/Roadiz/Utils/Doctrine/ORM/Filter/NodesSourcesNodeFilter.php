<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesBuildEvent;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Filter on nodeType fields when criteria contains nodeType. prefix.
 *
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
class NodesSourcesNodeFilter implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderNodesSourcesBuildEvent::class => [['onNodesSourcesQueryBuilderBuild', -10]],
        ];
    }

    /**
     * @param QueryBuilderNodesSourcesBuildEvent $event
     *
     * @return bool
     */
    protected function supports(QueryBuilderNodesSourcesBuildEvent $event): bool
    {
        return $event->supports() && false !== strpos($event->getProperty(), 'node.');
    }

    /**
     * @param QueryBuilderNodesSourcesBuildEvent $event
     */
    public function onNodesSourcesQueryBuilderBuild(QueryBuilderNodesSourcesBuildEvent $event)
    {
        if ($this->supports($event)) {
            // Prevent other query builder filters to execute
            $event->stopPropagation();
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            $qb = $event->getQueryBuilder();
            $baseKey = $simpleQB->getParameterKey($event->getProperty());

            if (!$simpleQB->joinExists(
                $simpleQB->getRootAlias(),
                EntityRepository::NODE_ALIAS
            )) {
                $qb->innerJoin(
                    $simpleQB->getRootAlias() . '.node',
                    EntityRepository::NODE_ALIAS
                );
            }

            $prefix = EntityRepository::NODE_ALIAS . '.';
            $key = str_replace('node.', '', $event->getProperty());
            $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
        }
    }
}
