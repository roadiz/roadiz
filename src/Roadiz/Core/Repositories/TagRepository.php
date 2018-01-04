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
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
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
    protected function filterByNodes(&$criteria, &$qb)
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
     * @param Query $finalQuery
     */
    protected function applyFilterByNodes(array &$criteria, &$finalQuery)
    {
        if (in_array('nodes', array_keys($criteria))) {
            if ($criteria['nodes'] instanceof Node) {
                $finalQuery->setParameter('nodes', $criteria['nodes']->getId());
            } elseif (is_array($criteria['nodes']) ||
                $criteria['nodes'] instanceof Collection) {
                $finalQuery->setParameter('nodes', $criteria['nodes']);
            } elseif (is_integer($criteria['nodes'])) {
                $finalQuery->setParameter('nodes', (int) $criteria['nodes']);
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
    protected function filterByCriteria(&$criteria, &$qb)
    {
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
            $baseKey = str_replace('.', '_', $key);
            /*
             * Search in translation fields
             */
            if (false !== strpos($key, 'translation.')) {
                $prefix = static::TRANSLATION_ALIAS . '.';
                $key = str_replace('translation.', '', $key);
            }

            /*
             * Search in node fields
             */
            if (false !== strpos($key, 'nodes.')) {
                $prefix = static::NODE_ALIAS . '.';
                $key = str_replace('nodes.', '', $key);
            }

            /*
             * Search in translatedTags fields
             */
            if (false !== strpos($key, 'translatedTag.')) {
                $prefix = 'tt.';
                $key = str_replace('translatedTag.', '', $key);
            }

            /*
             * Search in translation fields
             */
            if ($key == 'translation') {
                $prefix = 'tt.';
            }

            $qb->andWhere($this->buildComparison($value, $prefix, $key, $baseKey, $qb));
        }
    }
    /**
     * Bind parameters to generated query.
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyFilterByCriteria(&$criteria, &$finalQuery)
    {
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {
            $this->applyComparison($key, $value, $finalQuery);
        }
    }

    /**
     * Create filters according to any translation criteria OR argument.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     * @param Translation  $translation
     */
    protected function filterByTranslation(&$criteria, &$qb, &$translation = null)
    {
        if (isset($criteria['translation']) ||
            isset($criteria['translation.locale']) ||
            isset($criteria['translation.id'])) {
            $qb->innerJoin('tg.translatedTags', 'tt');
            $qb->innerJoin('tt.translation', static::TRANSLATION_ALIAS);
        } else {
            if (null !== $translation) {
                /*
                 * With a given translation
                 */
                $qb->innerJoin(
                    'tg.translatedTags',
                    'tt',
                    'WITH',
                    'tt.translation = :translation'
                );
            } else {
                /*
                 * With a null translation, just take the default one.
                 */
                $qb->innerJoin('tg.translatedTags', 'tt');
                $qb->innerJoin(
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
     * @param array $criteria
     * @param Query $finalQuery
     * @param Translation|null $translation
     */
    protected function applyTranslationByTag(
        array &$criteria,
        &$finalQuery,
        Translation &$translation = null
    ) {
        if (null !== $translation) {
            $finalQuery->setParameter('translation', $translation);
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
        array $criteria,
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
        array $criteria,
        Translation $translation = null
    ) {

        $qb = $this->createQueryBuilder('tg');
        $qb->select($qb->expr()->countDistinct('tg.id'));

        $this->filterByNodes($criteria, $qb);
        $this->filterByTranslation($criteria, $qb, $translation);
        $this->filterByCriteria($criteria, $qb);

        return $qb;
    }

    /**
     * Just like the findBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param Translation|null $translation
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
        $finalQuery = $query->getQuery();

        $this->applyFilterByNodes($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        if (null !== $limit &&
            null !== $offset) {
            /*
             * We need to use Doctrine paginator
             * if a limit is set because of the default inner join
             */
            return new Paginator($finalQuery);
        } else {
            try {
                return $finalQuery->getResult();
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
        $finalQuery = $query->getQuery();

        $this->applyFilterByNodes($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getSingleResult();
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
        $finalQuery = $query->getQuery();

        $this->applyFilterByNodes($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return (int) $finalQuery->getSingleScalarResult();
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
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            WHERE t.id = :tag_id
            AND tt.translation = :translation')
            ->setMaxResults(1)
            ->setParameter('tag_id', (int) $tagId)
            ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Translation $translation
     *
     * @return array
     */
    public function findAllWithTranslation(Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT tg, tt FROM RZ\Roadiz\Core\Entities\Tag tg
            INNER JOIN tg.translatedTags tt
            WHERE tt.translation = :translation')
            ->setParameter('translation', $translation);

        try {
            return $query->getResult();
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
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id
            AND tr.defaultTranslation = true')
            ->setMaxResults(1)
            ->setParameter('tag_id', (int) $tagId);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    public function findAllWithDefaultTranslation()
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE tr.defaultTranslation = true');
        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @param Translation $translation
     * @param Tag         $parent
     *
     * @return array
     */
    public function findByParentWithTranslation(Translation $translation, Tag $parent = null)
    {
        $query = null;

        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.id = :translation_id
            ORDER BY t.position ASC')
                ->setParameter('translation_id', (int) $translation->getId());
        } else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
                INNER JOIN t.translatedTags tt
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.id = :translation_id
                ORDER BY t.position ASC')
                ->setParameter('parent', $parent->getId())
                ->setParameter('translation_id', (int) $translation->getId());
        }

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Tag $parent
     *
     * @return array
     */
    public function findByParentWithDefaultTranslation(Tag $parent = null)
    {
        $query = null;
        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.defaultTranslation = true
            ORDER BY t.position ASC');
        } else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
                INNER JOIN t.translatedTags tt
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.defaultTranslation = true
                ORDER BY t.position ASC')
                ->setParameter('parent', $parent->getId());
        }

        try {
            return $query->getResult();
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
        $metadatas = $this->_em->getClassMetadata('RZ\Roadiz\Core\Entities\TagTranslation');
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
     * @param  Tag|null $parentTag [description]
     * @return int
     */
    public function findLatestPositionInParent(Tag $parentTag = null)
    {
        $query = $this->_em->createQuery('
            SELECT MAX(t.position)
            FROM RZ\Roadiz\Core\Entities\Tag t
            WHERE t.parent = :parent')
            ->setParameter('parent', $parentTag);

        try {
            return $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
