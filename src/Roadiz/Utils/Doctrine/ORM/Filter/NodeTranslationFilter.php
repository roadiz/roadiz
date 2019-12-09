<?php
declare(strict_types=1);
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeTranslationFilter.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\FilterQueryBuilderCriteriaEvent;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderBuildEvent;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NodeTranslationFilter.
 *
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
     * @param FilterQueryBuilderCriteriaEvent $event
     *
     * @return bool
     */
    protected function supports(FilterQueryBuilderCriteriaEvent $event): bool
    {
        return $event->supports() &&
            $event->getActualEntityName() === Node::class &&
            false !== strpos($event->getProperty(), 'translation');
    }

    /**
     * @param FilterQueryBuilderCriteriaEvent $event
     */
    public function onTranslationPrefixFilter(FilterQueryBuilderCriteriaEvent $event)
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
     * @param FilterQueryBuilderCriteriaEvent $event
     */
    public function onTranslationFilter(FilterQueryBuilderCriteriaEvent $event)
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
