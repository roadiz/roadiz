<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderBuildEvent;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Filter on translation fields when criteria contains translation. prefix.
 *
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
class NodeTranslationFilter implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderBuildEvent::class => [
                // This event must be the last to perform
                ['onTranslationPrefixFilter', 0],
                ['onTranslationFilter', -10],
            ]
        ];
    }

    /**
     * @param QueryBuilderBuildEvent $event
     *
     * @return bool
     */
    protected function supports(QueryBuilderBuildEvent $event): bool
    {
        return $event->supports() &&
            $event->getActualEntityName() === Node::class &&
            false !== strpos($event->getProperty(), 'translation');
    }

    /**
     * @param QueryBuilderBuildEvent $event
     */
    public function onTranslationPrefixFilter(QueryBuilderBuildEvent $event)
    {
        if ($this->supports($event)) {
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            if (false !== strpos($event->getProperty(), 'translation.')) {
                // Prevent other query builder filters to execute
                $event->stopPropagation();
                $qb = $event->getQueryBuilder();
                $baseKey = $simpleQB->getParameterKey($event->getProperty());

                if (!$simpleQB->joinExists(
                    $simpleQB->getRootAlias(),
                    EntityRepository::NODESSOURCES_ALIAS
                )
                ) {
                    $qb->innerJoin(
                        $simpleQB->getRootAlias() . '.nodeSources',
                        EntityRepository::NODESSOURCES_ALIAS
                    );
                }

                if (!$simpleQB->joinExists(
                    $simpleQB->getRootAlias(),
                    EntityRepository::TRANSLATION_ALIAS
                )
                ) {
                    $qb->innerJoin(
                        EntityRepository::NODESSOURCES_ALIAS . '.translation',
                        EntityRepository::TRANSLATION_ALIAS
                    );
                }

                $prefix = EntityRepository::TRANSLATION_ALIAS . '.';
                $key = str_replace('translation.', '', $event->getProperty());
                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
            }
        }
    }

    /**
     * @param QueryBuilderBuildEvent $event
     */
    public function onTranslationFilter(QueryBuilderBuildEvent $event)
    {
        if ($this->supports($event)) {
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            if ($event->getProperty() === 'translation') {
                // Prevent other query builder filters to execute
                $event->stopPropagation();
                $qb = $event->getQueryBuilder();
                $baseKey = $simpleQB->getParameterKey($event->getProperty());

                if (!$simpleQB->joinExists(
                    $simpleQB->getRootAlias(),
                    EntityRepository::NODESSOURCES_ALIAS
                )) {
                    $qb->innerJoin(
                        $simpleQB->getRootAlias() . '.nodeSources',
                        EntityRepository::NODESSOURCES_ALIAS
                    );
                }

                $prefix = EntityRepository::NODESSOURCES_ALIAS . '.';
                $key = $event->getProperty();
                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
            }
        }
    }
}
