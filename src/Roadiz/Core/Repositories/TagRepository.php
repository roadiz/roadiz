<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use RZ\Roadiz\Utils\StringHandler;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\Tag>
 */
class TagRepository extends EntityRepository
{
    /**
     * Add a node filtering to queryBuilder.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByNodes($criteria, QueryBuilder $qb)
    {
        if (key_exists('nodes', $criteria)) {
            if (is_array($criteria['nodes']) || $criteria['nodes'] instanceof Collection) {
                $qb->innerJoin(
                    'tg.nodes',
                    static::NODE_ALIAS,
                    'WITH',
                    'n.id IN (:nodes)'
                );
            } else {
                $qb->innerJoin(
                    'tg.nodes',
                    static::NODE_ALIAS,
                    'WITH',
                    'n.id = :nodes'
                );
            }
        }
    }

    /**
     * Bind node parameter to final query
     *
     * @param array $criteria
     * @param QueryBuilder $qb
     */
    protected function applyFilterByNodes(array &$criteria, QueryBuilder $qb)
    {
        if (key_exists('nodes', $criteria)) {
            if ($criteria['nodes'] instanceof Node) {
                $qb->setParameter('nodes', $criteria['nodes']->getId());
            } elseif (is_array($criteria['nodes']) ||
                $criteria['nodes'] instanceof Collection) {
                $qb->setParameter('nodes', $criteria['nodes']);
            } elseif (is_integer($criteria['nodes'])) {
                $qb->setParameter('nodes', (int) $criteria['nodes']);
            }
            unset($criteria['nodes']);
        }
    }

    /**
     * Bind parameters to generated query.
     *
     * @param array $criteria
     * @param QueryBuilder $qb
     */
    protected function applyFilterByCriteria(array &$criteria, QueryBuilder $qb)
    {
        /*
         * Reimplementing findBy features…
         */
        $simpleQB = new SimpleQueryBuilder($qb);
        foreach ($criteria as $key => $value) {
            $event = $this->dispatchQueryBuilderApplyEvent($qb, $key, $value);
            if (!$event->isPropagationStopped()) {
                $simpleQB->bindValue($key, $value);
            }
        }
    }

    /**
     * Create filters according to any translation criteria OR argument.
     *
     * @param array $criteria
     * @param QueryBuilder $qb
     * @param TranslationInterface|null $translation
     */
    protected function filterByTranslation($criteria, QueryBuilder $qb, TranslationInterface $translation = null)
    {
        if (isset($criteria['translation']) ||
            isset($criteria['translation.locale']) ||
            isset($criteria['translation.id'])) {
            $qb->leftJoin('tg.translatedTags', 'tt');
            $qb->leftJoin('tt.translation', static::TRANSLATION_ALIAS);
        } else {
            if (null !== $translation) {
                /*
                 * With a given translation
                 */
                $qb->leftJoin(
                    'tg.translatedTags',
                    'tt',
                    'WITH',
                    'tt.translation = :translation'
                );
            } else {
                /*
                 * With a null translation, just take the default one.
                 */
                $qb->leftJoin('tg.translatedTags', 'tt');
                $qb->leftJoin(
                    'tt.translation',
                    static::TRANSLATION_ALIAS,
                    'WITH',
                    't.defaultTranslation = true'
                );
            }
        }
    }

    /**
     * Bind translation parameter to final query
     *
     * @param QueryBuilder $qb
     * @param TranslationInterface|null $translation
     */
    protected function applyTranslationByTag(
        QueryBuilder $qb,
        TranslationInterface $translation = null
    ) {
        if (null !== $translation) {
            $qb->setParameter('translation', $translation);
        }
    }

    /**
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array            $criteria
     * @param array|null       $orderBy
     * @param integer|null     $limit
     * @param integer|null     $offset
     * @param TranslationInterface|null $translation
     *
     * @return QueryBuilder
     */
    protected function getContextualQueryWithTranslation(
        array &$criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        TranslationInterface $translation = null
    ) {
        $qb = $this->createQueryBuilder(EntityRepository::TAG_ALIAS);
        $qb->addSelect('tt');
        $this->filterByNodes($criteria, $qb);
        $this->filterByTranslation($criteria, $qb, $translation);
        $this->prepareComparisons($criteria, $qb, EntityRepository::TAG_ALIAS);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy(EntityRepository::TAG_ALIAS . '.' . $key, $value);
            }
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }
    /**
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array $criteria
     * @param TranslationInterface|null $translation
     *
     * @return QueryBuilder
     */
    protected function getCountContextualQueryWithTranslation(
        array &$criteria,
        TranslationInterface $translation = null
    ) {
        $qb = $this->createQueryBuilder(EntityRepository::TAG_ALIAS);
        $this->filterByNodes($criteria, $qb);
        $this->filterByTranslation($criteria, $qb, $translation);
        $this->prepareComparisons($criteria, $qb, EntityRepository::TAG_ALIAS);

        return $qb->select($qb->expr()->countDistinct(EntityRepository::TAG_ALIAS));
    }

    /**
     * Just like the findBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|string[]|null                     $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param TranslationInterface|null               $translation
     *
     * @return array|Paginator
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        TranslationInterface $translation = null
    ) {
        $qb = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation
        );

        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByNodes($criteria, $qb);
        $this->applyFilterByCriteria($criteria, $qb);
        $this->applyTranslationByTag($qb, $translation);
        $query = $qb->getQuery()->setQueryCacheLifetime(0);
        $this->dispatchQueryEvent($query);

        if (null !== $limit &&
            null !== $offset) {
            /*
             * We need to use Doctrine paginator
             * if a limit is set because of the default inner join
             */
            return new Paginator($query);
        } else {
            return $query->getResult();
        }
    }
    /**
     * Just like the findOneBy method but with relational criteria.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param TranslationInterface|null $translation
     *
     * @return Tag|null
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        TranslationInterface $translation = null
    ) {
        $qb = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            1,
            0,
            $translation
        );

        $this->dispatchQueryBuilderEvent($qb, $this->getEntityName());
        $this->applyFilterByNodes($criteria, $qb);
        $this->applyFilterByCriteria($criteria, $qb);
        $this->applyTranslationByTag($qb, $translation);
        $query = $qb->getQuery()->setQueryCacheLifetime(0);
        $this->dispatchQueryEvent($query);

        return $query->getOneOrNullResult();
    }
    /**
     * Just like the countBy method but with relational criteria.
     *
     * @param array $criteria
     * @param TranslationInterface|null $translation
     * @return int
     */
    public function countBy(
        $criteria,
        TranslationInterface $translation = null
    ) {
        $query = $this->getCountContextualQueryWithTranslation(
            $criteria,
            $translation
        );

        $this->dispatchQueryBuilderEvent($query, $this->getEntityName());
        $this->applyFilterByNodes($criteria, $query);
        $this->applyFilterByCriteria($criteria, $query);
        $this->applyTranslationByTag($query, $translation);

        return (int) $query->getQuery()->setQueryCacheLifetime(0)->getSingleScalarResult();
    }

    /**
     * @param int $tagId
     * @param TranslationInterface $translation
     *
     * @return Tag|null
     */
    public function findWithTranslation($tagId, TranslationInterface $translation)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->andWhere($qb->expr()->eq('tt.translation', ':translation'))
            ->andWhere($qb->expr()->eq('t.id', ':id'))
            ->setParameter(':translation', $translation)
            ->setParameter(':id', $tagId)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->setQueryCacheLifetime(0)->getOneOrNullResult();
    }

    /**
     * @param TranslationInterface $translation
     * @return Tag[]
     */
    public function findAllWithTranslation(TranslationInterface $translation)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->andWhere($qb->expr()->eq('tt.translation', ':translation'))
            ->setParameter(':translation', $translation)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $tagId
     *
     * @return Tag|null
     */
    public function findWithDefaultTranslation($tagId)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->leftJoin('tt.translation', 'tr')
            ->andWhere($qb->expr()->eq('tr.defaultTranslation', ':defaultTranslation'))
            ->andWhere($qb->expr()->eq('t.id', ':id'))
            ->setParameter(':defaultTranslation', true)
            ->setParameter(':id', $tagId)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Tag[]
     */
    public function findAllWithDefaultTranslation()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->leftJoin('tt.translation', 'tr')
            ->addOrderBy('t.tagName', 'ASC')
            ->andWhere($qb->expr()->eq('tr.defaultTranslation', ':defaultTranslation'))
            ->setParameter(':defaultTranslation', true)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Tag[]
     */
    public function findAllColored()
    {
        $qb = $this->createQueryBuilder('t');
        $qb
            ->andWhere($qb->expr()->isNotNull('t.color'))
            ->andWhere($qb->expr()->notIn('t.color', ':colored'))
            ->addOrderBy('t.position', 'DESC')
            ->setParameter(':colored', [
                '#000000',
                '#000',
                '#fff',
                '#ffffff',
            ])
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @param Node $parentNode
     * @param TranslationInterface|null $translation
     *
     * @return Tag[]
     */
    public function findAllLinkedToNodeChildren(Node $parentNode, ?TranslationInterface $translation = null)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t')
            ->addSelect('tt')
            ->addSelect('tr')
            ->innerJoin('t.nodes', 'n')
            ->innerJoin('n.parent', 'pn')
            ->leftJoin('t.translatedTags', 'tt')
            ->leftJoin('tt.translation', 'tr')
            ->andWhere($qb->expr()->eq('pn', ':parentNode'))
            ->setParameter('parentNode', $parentNode)
            ->addOrderBy('t.tagName', 'ASC')
        ;
        if (null !== $translation) {
            $qb->innerJoin('n.nodeSources', 'ns')
                ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
                ->andWhere($qb->expr()->eq('tt.translation', ':translation'))
                ->setParameter('translation', $translation);
        }
        return $qb->getQuery()
            ->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->setQueryCacheLifetime(120)
            ->getResult()
        ;
    }

    /**
     * @param TranslationInterface $translation
     * @param Tag $parent
     *
     * @return Tag[]
     */
    public function findByParentWithTranslation(TranslationInterface $translation, Tag $parent = null)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->andWhere($qb->expr()->eq('tt.translation', ':translation'))
            ->addOrderBy('t.position', 'ASC')
            ->setParameter(':translation', $translation)
        ;

        if (null !== $parent) {
            $qb->andWhere($qb->expr()->eq('t.parent', ':parent'))
                ->setParameter(':parent', $parent);
        } else {
            $qb->andWhere($qb->expr()->isNull('t.parent'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Tag|null $parent
     *
     * @return Tag[]
     */
    public function findByParentWithDefaultTranslation(Tag $parent = null)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->leftJoin('tt.translation', 'tr')
            ->addOrderBy('t.position', 'ASC')
            ->andWhere($qb->expr()->eq('tr.defaultTranslation', ':defaultTranslation'))
            ->setParameter(':defaultTranslation', true)
        ;

        if (null !== $parent) {
            $qb->andWhere($qb->expr()->eq('t.parent', ':parent'))
                ->setParameter(':parent', $parent);
        } else {
            $qb->andWhere($qb->expr()->isNull('t.parent'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns only Tags that have children.
     *
     * @param Tag|null $parent
     * @return Tag[]
     */
    public function findByParentWithChildrenAndDefaultTranslation(Tag $parent = null)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->leftJoin('tt.translation', 'tr')
            ->innerJoin('t.children', 'ct')
            ->andWhere($qb->expr()->eq('tr.defaultTranslation', ':defaultTranslation'))
            ->andWhere($qb->expr()->isNotNull('ct.id'))
            ->addOrderBy('t.position', 'ASC')
            ->setParameter(':defaultTranslation', true)
        ;

        if (null !== $parent) {
            $qb->andWhere($qb->expr()->eq('t.parent', ':parent'))
                ->setParameter(':parent', $parent);
        } else {
            $qb->andWhere($qb->expr()->isNull('t.parent'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Create a Criteria object from a search pattern and additional fields.
     *
     * @param string $pattern Search pattern
     * @param QueryBuilder $qb QueryBuilder to pass
     * @param array $criteria Additional criteria
     * @param string $alias SQL query table alias
     *
     * @return QueryBuilder
     */
    protected function createSearchBy(
        $pattern,
        QueryBuilder $qb,
        array &$criteria = [],
        $alias = EntityRepository::DEFAULT_ALIAS
    ) {
        $this->classicLikeComparison($pattern, $qb, $alias);

        /*
         * Search in translations
         */
        $qb->leftJoin($alias . '.translatedTags', 'tt');
        $criteriaFields = [];
        $metadatas = $this->_em->getClassMetadata(TagTranslation::class);
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $this->searchableTypes)) {
                $criteriaFields[$field] = '%' . strip_tags((string) $pattern) . '%';
            }
        }
        foreach ($criteriaFields as $key => $value) {
            $fullKey = sprintf('LOWER(%s)', 'tt.' . $key);
            $qb->orWhere($qb->expr()->like($fullKey, $qb->expr()->literal($value)));
        }

        $qb = $this->prepareComparisons($criteria, $qb, $alias);

        return $qb;
    }

    /**
     * @param  array        $criteria
     * @param  QueryBuilder $qb
     * @param  string       $alias
     * @return QueryBuilder
     */
    protected function prepareComparisons(array &$criteria, QueryBuilder $qb, $alias)
    {
        $simpleQB = new SimpleQueryBuilder($qb);
        foreach ($criteria as $key => $value) {
            /*
             * Main QueryBuilder dispatch loop for
             * custom properties criteria.
             */
            $event = $this->dispatchQueryBuilderBuildEvent($qb, $key, $value);

            if (!$event->isPropagationStopped()) {
                /*
                 * Search in node fields
                 */
                if ($key == 'nodes') {
                    continue;
                }

                /*
                 * compute prefix for
                 * filtering node, and sources relation fields
                 */
                $prefix = $alias;

                // Dots are forbidden in field definitions
                $baseKey = $simpleQB->getParameterKey($key);

                if (false !== strpos($key, 'translation.')) {
                    /*
                     * Search in translation fields
                     */
                    $prefix = static::TRANSLATION_ALIAS . '.';
                    $key = str_replace('translation.', '', $key);
                } elseif (false !== strpos($key, 'nodes.')) {
                    /*
                     * Search in node fields
                     */
                    $prefix = static::NODE_ALIAS . '.';
                    $key = str_replace('nodes.', '', $key);
                } elseif (false !== strpos($key, 'translatedTag.')) {
                    /*
                     * Search in translatedTags fields
                     */
                    $prefix = 'tt.';
                    $key = str_replace('translatedTag.', '', $key);
                } elseif ($key === 'translation') {
                    /*
                     * Search in translation fields
                     */
                    $prefix = 'tt.';
                }
                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($value, $prefix, $key, $baseKey));
            }
        }

        return $qb;
    }

    /**
     * Find a tag according to the given path or create it.
     *
     * @param string $tagPath
     * @param TranslationInterface|null $translation
     *
     * @return Tag|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOrCreateByPath(string $tagPath, ?TranslationInterface $translation = null)
    {
        $tagPath = trim($tagPath);
        $tags = explode('/', $tagPath);
        $tags = array_filter($tags);

        if (count($tags) === 0) {
            return null;
        }

        $tagName = $tags[count($tags) - 1];
        $tag = $this->findOneByTagName(StringHandler::slugify($tagName));

        if (null === $tag) {
            /** @var TagTranslation|null $ttag */
            $ttag = $this->_em->getRepository(TagTranslation::class)->findOneByName($tagName);
            if (null !== $ttag) {
                $tag = $ttag->getTag();
            }
        }

        if (null === $tag) {
            /*
             * Creation of a new tag
             * before linking it to the node
             */
            $parentName = null;
            $parentTag = null;

            if (count($tags) > 1) {
                $parentName = $tags[count($tags) - 2];
                $parentTag = $this->findOneByTagName(StringHandler::slugify($parentName));

                if (null === $parentTag) {
                    $ttagParent = $this->_em->getRepository(TagTranslation::class)->findOneByName($parentName);
                    if (null !== $ttagParent) {
                        $parentTag = $ttagParent->getTag();
                    }
                }
            }
            if (null === $translation) {
                $translation = $this->_em->getRepository(Translation::class)->findDefault();
            }

            $tag = new Tag();
            $tag->setTagName($tagName);
            $translatedTag = new TagTranslation($tag, $translation);
            $translatedTag->setName($tagName);
            $tag->getTranslatedTags()->add($translatedTag);

            if (null !== $parentTag) {
                $tag->setParent($parentTag);
            }

            $this->_em->persist($translatedTag);
            $this->_em->persist($tag);
            $this->_em->flush();
        }

        return $tag;
    }

    /**
     * Find a tag according to the given path.
     *
     * @param string $tagPath
     *
     * @return Tag|null
     */
    public function findByPath(string $tagPath)
    {
        $tagPath = trim($tagPath);
        $tags = explode('/', $tagPath);
        $tags = array_filter($tags);
        $lastToken = count($tags) - 1;
        $tagName = count($tags) > 0 ? $tags[$lastToken] : $tagPath;

        $tag = $this->findOneByTagName(StringHandler::slugify($tagName));

        if (null === $tag) {
            $ttag = $this->_em->getRepository(TagTranslation::class)->findOneByName($tagName);
            if (null !== $ttag) {
                $tag = $ttag->getTag();
            }
        }

        return $tag;
    }

    /**
     * Get latest position in parent.
     *
     * Parent can be null for tag root
     *
     * @param  Tag|null $parent
     * @return int
     */
    public function findLatestPositionInParent(Tag $parent = null)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select($qb->expr()->max('t.position'));

        if (null !== $parent) {
            $qb->andWhere($qb->expr()->eq('t.parent', ':parent'))
                ->setParameter(':parent', $parent);
        } else {
            $qb->andWhere($qb->expr()->isNull('t.parent'));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
