<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file TagRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use RZ\Roadiz\Utils\StringHandler;

/**
 * {@inheritdoc}
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
        if (in_array('nodes', array_keys($criteria))) {
            if (is_array($criteria['nodes']) ||
                (is_object($criteria['nodes']) &&
                    $criteria['nodes'] instanceof Collection)) {
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
        if (in_array('nodes', array_keys($criteria))) {
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
     * Reimplementing findBy features… with extra things
     *
     * * key => array('<=', $value)
     * * key => array('<', $value)
     * * key => array('>=', $value)
     * * key => array('>', $value)
     * * key => array('BETWEEN', $value, $value)
     * * key => array('LIKE', $value)
     * * key => array('NOT IN', $array)
     * * key => 'NOT NULL'
     *
     * You can filter with translations relation, examples:
     *
     * * `translation => $object`
     * * `translation.locale => 'fr_FR'`
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByCriteria($criteria, QueryBuilder $qb)
    {
        $simpleQB = new SimpleQueryBuilder($qb);
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {
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
            $prefix = static::TAG_ALIAS . '.';

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
            $simpleQB->bindValue($key, $value);
        }
    }

    /**
     * Create filters according to any translation criteria OR argument.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     * @param Translation  $translation
     */
    protected function filterByTranslation($criteria, QueryBuilder $qb, Translation $translation = null)
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
     * @param Translation|null $translation
     */
    protected function applyTranslationByTag(
        QueryBuilder $qb,
        Translation $translation = null
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
     * @param Translation|null $translation
     *
     * @return QueryBuilder
     */
    protected function getContextualQueryWithTranslation(
        array &$criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null
    ) {

        $qb = $this->createQueryBuilder('tg');
        $qb->addSelect('tt');

        $this->filterByNodes($criteria, $qb);
        $this->filterByTranslation($criteria, $qb, $translation);
        $this->filterByCriteria($criteria, $qb);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy(static::TAG_ALIAS . '.' . $key, $value);
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
     * @param array                                   $criteria
     * @param Translation|null $translation
     *
     * @return QueryBuilder
     */
    protected function getCountContextualQueryWithTranslation(
        array &$criteria,
        Translation $translation = null
    ) {
        $qb = $this->getContextualQueryWithTranslation($criteria, null, null, null, $translation);
        return $qb->select($qb->expr()->countDistinct('tg.id'));
    }

    /**
     * Just like the findBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|string[]|null                     $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param Translation|null                        $translation
     *
     * @return array|Paginator
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null
    ) {
        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation
        );

        $this->dispatchQueryBuilderEvent($query, $this->getEntityName());
        $this->applyFilterByNodes($criteria, $query);
        $this->applyFilterByCriteria($criteria, $query);
        $this->applyTranslationByTag($query, $translation);

        if (null !== $limit &&
            null !== $offset) {
            /*
             * We need to use Doctrine paginator
             * if a limit is set because of the default inner join
             */
            return new Paginator($query);
        } else {
            try {
                return $query->getQuery()->getResult();
            } catch (NoResultException $e) {
                return [];
            }
        }
    }
    /**
     * Just like the findOneBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param Translation|null $translation
     *
     * @return Tag|null
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        Translation $translation = null
    ) {

        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            1,
            0,
            $translation
        );

        $this->dispatchQueryBuilderEvent($query, $this->getEntityName());
        $this->applyFilterByNodes($criteria, $query);
        $this->applyFilterByCriteria($criteria, $query);
        $this->applyTranslationByTag($query, $translation);

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
    /**
     * Just like the countBy method but with relational criteria.
     *
     * @param array            $criteria
     * @param Translation|null $translation
     * @return int
     */
    public function countBy(
        $criteria,
        Translation $translation = null
    ) {
        $query = $this->getCountContextualQueryWithTranslation(
            $criteria,
            $translation
        );

        $this->dispatchQueryBuilderEvent($query, $this->getEntityName());
        $this->applyFilterByNodes($criteria, $query);
        $this->applyFilterByCriteria($criteria, $query);
        $this->applyTranslationByTag($query, $translation);

        try {
            return (int) $query->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * @param integer     $tagId
     * @param Translation $translation
     *
     * @return Tag|null
     */
    public function findWithTranslation($tagId, Translation $translation)
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

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Translation $translation
     * @return Tag[]
     */
    public function findAllWithTranslation(Translation $translation)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->andWhere($qb->expr()->eq('tt.translation', ':translation'))
            ->setParameter(':translation', $translation)
        ;

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @param integer $tagId
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

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
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

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @param Translation $translation
     * @param Tag $parent
     *
     * @return Tag[]
     */
    public function findByParentWithTranslation(Translation $translation, Tag $parent = null)
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

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @param Tag $parent
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

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
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

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Create a Criteria object from a search pattern and additionnal fields.
     *
     * @param string $pattern Search pattern
     * @param QueryBuilder $qb QueryBuilder to pass
     * @param array $criteria Additionnal criteria
     * @param string $alias SQL query table alias
     *
     * @return QueryBuilder
     */
    protected function createSearchBy(
        $pattern,
        QueryBuilder $qb,
        array &$criteria = [],
        $alias = "obj"
    ) {
        $this->classicLikeComparison($pattern, $qb, $alias);

        /*
         * Search in translations
         */
        $qb->leftJoin('obj.translatedTags', 'tt');
        $criteriaFields = [];
        $metadatas = $this->_em->getClassMetadata(TagTranslation::class);
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $this->searchableTypes)) {
                $criteriaFields[$field] = '%' . strip_tags(strtolower($pattern)) . '%';
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

        return $qb;
    }

    /**
     * Find a tag according to the given path or create it.
     *
     * @param string $tagPath
     *
     * @return \RZ\Roadiz\Core\Entities\Tag
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOrCreateByPath($tagPath)
    {
        $tagPath = trim($tagPath);
        $tags = explode('/', $tagPath);
        $tags = array_filter($tags);

        $tagName = $tags[count($tags) - 1];
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

        $tag = $this->findOneByTagName(StringHandler::slugify($tagName));

        if (null === $tag) {
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
            $trans = $this->_em->getRepository(Translation::class)->findDefault();

            $tag = new Tag();
            $tag->setTagName($tagName);
            $translatedTag = new TagTranslation($tag, $trans);
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
     * @return \RZ\Roadiz\Core\Entities\Tag|null
     */
    public function findByPath($tagPath)
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

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
