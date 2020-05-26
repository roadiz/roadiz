<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderBuildEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderNodesSourcesBuildEvent;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
class ANodesFilter implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderNodesSourcesBuildEvent::class => [['onNodesSourcesQueryBuilderBuild', 40]],
            QueryBuilderBuildEvent::class => [['onNodeQueryBuilderBuild', 30]]
        ];
    }

    /**
     * @return string
     */
    protected function getProperty()
    {
        return 'aNodes';
    }

    /**
     * @return string
     */
    protected function getNodeJoinAlias()
    {
        return 'a_n';
    }

    /**
     * @return string
     */
    protected function getNodeFieldJoinAlias()
    {
        return 'a_n_f';
    }

    /**
     * @param QueryBuilderBuildEvent $event
     */
    public function onNodeQueryBuilderBuild(QueryBuilderBuildEvent $event)
    {
        if ($event->supports() && $event->getActualEntityName() === Node::class) {
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            if (false !== strpos($event->getProperty(), $this->getProperty() . '.')) {
                // Prevent other query builder filters to execute
                $event->stopPropagation();
                $qb = $event->getQueryBuilder();
                $baseKey = $simpleQB->getParameterKey($event->getProperty());

                if (!$simpleQB->joinExists(
                    $simpleQB->getRootAlias(),
                    $this->getNodeJoinAlias()
                )) {
                    $qb->innerJoin(
                        $simpleQB->getRootAlias() . '.' . $this->getProperty(),
                        $this->getNodeJoinAlias()
                    );
                }
                if (false !== strpos($event->getProperty(), $this->getProperty() . '.field.')) {
                    if (!$simpleQB->joinExists(
                        $simpleQB->getRootAlias(),
                        $this->getNodeFieldJoinAlias()
                    )) {
                        $qb->innerJoin(
                            $this->getNodeJoinAlias() . '.field',
                            $this->getNodeFieldJoinAlias()
                        );
                    }
                    $prefix = $this->getNodeFieldJoinAlias() . '.';
                    $key = str_replace($this->getProperty() . '.field.', '', $event->getProperty());
                } else {
                    $prefix = $this->getNodeJoinAlias() . '.';
                    $key = str_replace($this->getProperty() . '.', '', $event->getProperty());
                }

                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
            }
        }
    }

    /**
     * @param QueryBuilderNodesSourcesBuildEvent $event
     */
    public function onNodesSourcesQueryBuilderBuild(QueryBuilderNodesSourcesBuildEvent $event)
    {
        if ($event->supports()) {
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            if (false !== strpos($event->getProperty(), 'node.' . $this->getProperty() . '.')) {
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
                    $this->getNodeJoinAlias()
                )) {
                    $qb->innerJoin(
                        EntityRepository::NODE_ALIAS . '.' . $this->getProperty(),
                        $this->getNodeJoinAlias()
                    );
                }
                if (false !== strpos($event->getProperty(), 'node.' . $this->getProperty() . '.field.')) {
                    if (!$simpleQB->joinExists(
                        $simpleQB->getRootAlias(),
                        $this->getNodeFieldJoinAlias()
                    )) {
                        $qb->innerJoin(
                            $this->getNodeJoinAlias() . '.field',
                            $this->getNodeFieldJoinAlias()
                        );
                    }
                    $prefix = $this->getNodeFieldJoinAlias() . '.';
                    $key = str_replace('node.' . $this->getProperty() . '.field.', '', $event->getProperty());
                } else {
                    $prefix = $this->getNodeJoinAlias() . '.';
                    $key = str_replace('node.' . $this->getProperty() . '.', '', $event->getProperty());
                }

                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
            }
        }
    }
}
