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
 * @file NodeRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * NodeRepository
 */
class NodeRepository extends EntityRepository
{
    /**
     * Add a tag filtering to queryBuilder.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByTag(&$criteria, &$qb)
    {
        if (in_array('tags', array_keys($criteria))) {
            if (is_array($criteria['tags'])) {
                if (in_array("tagExclusive", array_keys($criteria))
                    && $criteria["tagExclusive"] === true) {
                    $node = static::getNodeIdsByTagExcl($criteria['tags'], $this->_em);
                    $criteria["id"] = $node;
                    unset($criteria["tagExclusive"]);
                    unset($criteria['tags']);
                } else {
                    $qb->innerJoin(
                        'n.tags',
                        'tg',
                        'WITH',
                        'tg.id IN (:tags)'
                    );
                }
            } else {
                $qb->innerJoin(
                    'n.tags',
                    'tg',
                    'WITH',
                    'tg.id = :tags'
                );
            }
        }
    }

    /**
     * Search NodeId exclusively.
     *
     * @param  array        $tags
     * @param  EntityManager $em
     *
     * @return array
     */
    public static function getNodeIdsByTagExcl($tags, EntityManager $em)
    {
        $qb = $em->createQueryBuilder();

        $qb->select("nj.id")
            ->addSelect("COUNT(t.id) as num")
            ->from("RZ\Roadiz\Core\Entities\Tag", "t")
            ->leftJoin("t.nodes", "nj");
        foreach ($tags as $key => $tag) {
            $qb->orWhere($qb->expr()->eq('t.id', ':tag' . $key));
        }
        $qb->groupBy("nj.id");
        $query = $qb->getQuery();
        foreach ($tags as $key => $tag) {
            $query->setParameter("tag" . $key, $tag);
        }
        $results = $query->getResult();
        $count = count($tags);
        $nodes = [];
        foreach ($results as $key => $result) {
            if ($count === (int) $result["num"]) {
                $nodes[] = $result["id"];
            }
        }
        return $nodes;
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
            if ($key == "tags" || $key == "tagExclusive") {
                continue;
            }

            /*
             * compute prefix for
             * filtering node, and sources relation fields
             */
            $prefix = 'n.';

            // Dots are forbidden in field definitions
            $baseKey = str_replace('.', '_', $key);
            /*
             * Search in translation fields
             */
            if (false !== strpos($key, 'translation.')) {
                $prefix = 't.';
                $key = str_replace('translation.', '', $key);
            }
            /*
             * Search in nodeSource fields
             */
            if ($key == 'translation') {
                $prefix = 'ns.';
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
            if ($key == "tags" || $key == "tagExclusive") {
                continue;
            }

            $this->applyComparison($key, $value, $finalQuery);
        }
    }

    /**
     * Bind tag parameter to final query
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyFilterByTag(array &$criteria, &$finalQuery)
    {
        if (in_array('tags', array_keys($criteria))) {
            if (is_object($criteria['tags'])) {
                $finalQuery->setParameter('tags', $criteria['tags']->getId());
            } elseif (is_array($criteria['tags'])) {
                $finalQuery->setParameter('tags', $criteria['tags']);
            } elseif (is_integer($criteria['tags'])) {
                $finalQuery->setParameter('tags', (int) $criteria['tags']);
            }
            unset($criteria["tags"]);
        }
    }

    /**
     * Bind translation parameter to final query
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyTranslationByTag(
        array &$criteria,
        &$finalQuery,
        &$translation = null
    ) {
        if (null !== $translation) {
            $finalQuery->setParameter('translation', $translation);
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
            isset($criteria['translation.id']) ||
            isset($criteria['translation.available'])) {
            $qb->innerJoin('n.nodeSources', 'ns');
            $qb->innerJoin('ns.translation', 't');

        } else {
            if (null !== $translation) {
                /*
                 * With a given translation
                 */
                $qb->innerJoin(
                    'n.nodeSources',
                    'ns',
                    'WITH',
                    'ns.translation = :translation'
                );
            } else {
                /*
                 * With a null translation, not filter by translation to enable
                 * nodes with only one translation which is not the default one.
                 */
                $qb->innerJoin('n.nodeSources', 'ns');
            }
        }
    }
    /**
     * @param array                &$criteria
     * @param QueryBuilder         &$qb
     * @param AuthorizationChecker|null &$authorizationChecker
     * @param boolean $preview
     */
    protected function filterByAuthorizationChecker(
        &$criteria,
        &$qb,
        AuthorizationChecker &$authorizationChecker = null,
        $preview = false
    ) {
        $backendUser = null !== $authorizationChecker &&
        $authorizationChecker->isGranted(Role::ROLE_BACKEND_USER) &&
        $preview === true;

        if ($backendUser) {
            /*
             * Forbid deleted node for anonymous and backend users.
             */
            $qb->andWhere($qb->expr()->lte('n.status', Node::PUBLISHED));
        } elseif (null !== $authorizationChecker) {
            /*
             * Forbid unpublished node for anonymous and not backend users.
             */
            $qb->andWhere($qb->expr()->eq('n.status', Node::PUBLISHED));
        }
    }

    /**
     * Create a securized query with node.published = true if user is
     * not a Backend user and if authorizationChecker is defined.
     *
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param AuthorizationChecker|null                    $authorizationChecker
     * @param boolean                     $preview
     *
     * @return QueryBuilder
     */
    protected function getContextualQueryWithTranslation(
        array &$criteria,
        array &$orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'n, ns')
            ->add('from', $this->getEntityName() . ' n');

        $this->filterByTranslation($criteria, $qb, $translation);

        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb);
        $this->filterByCriteria($criteria, $qb);
        $this->filterByAuthorizationChecker($criteria, $qb, $authorizationChecker, $preview);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy('n.' . $key, $value);
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
     * Create a securized count query with node.published = true if user is
     * not a Backend user and if authorizationChecker is defined.
     *
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array                                   $criteria
     * @param RZ\Roadiz\Core\Entities\Translation|null $authorizationChecker
     * @param AuthorizationChecker|null                    $authorizationChecker
     * @param boolean                     $preview
     *
     * @return QueryBuilder
     */
    protected function getCountContextualQueryWithTranslation(
        array &$criteria,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'count(n.id)')
            ->add('from', $this->getEntityName() . ' n');

        $this->filterByTranslation($criteria, $qb, $translation);
        /*
         * Filtering by tag
         */
        $this->filterByTag($criteria, $qb);
        $this->filterByCriteria($criteria, $qb);
        $this->filterByAuthorizationChecker($criteria, $qb, $authorizationChecker, $preview);

        return $qb;
    }
    /**
     * Just like the findBy method but with relational criteria.
     *
     * Reimplementing findBy features… with extra things:
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
     * Or filter by tags:
     *
     * * `tags => $tag1`
     * * `tags => [$tag1, $tag2]`
     * * `tags => [$tag1, $tag2], tagExclusive => true`
     *
     * @param array                                    $criteria
     * @param array|null                               $orderBy
     * @param integer|null                             $limit
     * @param integer|null                             $offset
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param AuthorizationChecker|null                $authorizationChecker
     * @param boolean                                  $preview
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation,
            $authorizationChecker,
            $preview
        );

        $finalQuery = $query->getQuery();

        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return new ArrayCollection();
        }
    }
    /**
     * Just like the findOneBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param AuthorizationChecker|null                    $authorizationChecker
     * @param boolean                                  $preview
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            1,
            0,
            $translation,
            $authorizationChecker,
            $preview
        );

        $finalQuery = $query->getQuery();

        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
    /**
     * Just like the countBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param AuthorizationChecker|null                    $authorizationChecker
     * @param boolean                                  $preview
     *
     * @return int
     */
    public function countBy(
        $criteria,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $query = $this->getCountContextualQueryWithTranslation(
            $criteria,
            $translation,
            $authorizationChecker,
            $preview
        );

        $finalQuery = $query->getQuery();
        $this->applyFilterByTag($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
    /**
     * Just like the findBy method but with a given Translation and optional
     * AuthorizationChecker.
     *
     * If no translation nor authorizationChecker is given, the vanilla `findBy`
     * method will be called instead.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param AuthorizationChecker|null                    $authorizationChecker
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByWithTranslation(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null
    ) {
        return $this->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation,
            $authorizationChecker
        );
    }

    /**
     * Just like the findOneBy method but with a given Translation and optional
     * AuthorizationChecker.
     *
     * If no translation nor authorizationChecker is given, the vanilla `findOneBy`
     * method will be called instead.
     *
     * @param array                                   $criteria
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param AuthorizationChecker|null $authorizationChecker
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findOneByWithTranslation(
        array $criteria,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null
    ) {
        return $this->findOneBy(
            $criteria,
            null,
            $translation,
            $authorizationChecker
        );
    }

    /**
     * Find one Node with its Id and a given translation.
     *
     * @param integer                            $nodeId
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findWithTranslation(
        $nodeId,
        Translation $translation,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.id = :nodeId AND ns.translation = :translation';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('nodeId', (int) $nodeId)
            ->setParameter('translation', $translation);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find one Node with its Id and the default translation.
     *
     * @param integer              $nodeId
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findWithDefaultTranslation(
        $nodeId,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.id = :nodeId AND t.defaultTranslation = true';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('nodeId', (int) $nodeId);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find one Node with its nodeName and a given translation.
     *
     * @param string                             $nodeName
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findByNodeNameWithTranslation(
        $nodeName,
        Translation $translation,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.nodeName = :nodeName AND ns.translation = :translation';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('nodeName', $nodeName)
            ->setParameter('translation', $translation);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find one Node with its nodeName and the default translation.
     *
     * @param string               $nodeName
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findByNodeNameWithDefaultTranslation(
        $nodeName,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.nodeName = :nodeName AND t.defaultTranslation = true';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('nodeName', $nodeName);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find the Home node with a given translation.
     *
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findHomeWithTranslation(
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        if (null === $translation) {
            return $this->findHomeWithDefaultTranslation($authorizationChecker);
        }

        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.home = true AND ns.translation = :translation';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('translation', $translation);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find the Home node with the default translation.
     *
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findHomeWithDefaultTranslation(
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.home = true AND t.defaultTranslation = true';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node        $node
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     * @param AuthorizationChecker|null               $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildrenWithTranslation(
        Node $node,
        Translation $translation,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.parent = :node AND ns.translation = :translation';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('node', $node)
            ->setParameter('translation', $translation);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     * @param RZ\Roadiz\Core\Entities\Node        $parent
     * @param AuthorizationChecker|null               $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByParentWithTranslation(
        Translation $translation,
        Node $parent = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
                     INNER JOIN n.nodeSources ns
                     INNER JOIN ns.translation t';

        if ($parent === null) {
            $txtQuery .= PHP_EOL . 'WHERE n.parent IS NULL';
        } else {
            $txtQuery .= PHP_EOL . 'WHERE n.parent = :parent';
        }

        $txtQuery .= ' AND t.id = :translation_id';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $txtQuery .= ' ORDER BY n.position ASC';

        if ($parent === null) {
            $query = $this->_em->createQuery($txtQuery)
                ->setParameter('translation_id', (int) $translation->getId());
        } else {
            $query = $this->_em->createQuery($txtQuery)
                ->setParameter('parent', $parent)
                ->setParameter('translation_id', (int) $translation->getId());
        }

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $parent
     * @param AuthorizationChecker|null        $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByParentWithDefaultTranslation(
        Node $parent = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $txtQuery = 'SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
                     INNER JOIN n.nodeSources ns
                     INNER JOIN ns.translation t';

        if ($parent === null) {
            $txtQuery .= PHP_EOL . 'WHERE n.parent IS NULL';
        } else {
            $txtQuery .= PHP_EOL . 'WHERE n.parent = :parent';
        }

        $txtQuery .= ' AND t.defaultTranslation = true';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $txtQuery .= ' ORDER BY n.position ASC';

        if ($parent === null) {
            $query = $this->_em->createQuery($txtQuery);
        } else {
            $query = $this->_em->createQuery($txtQuery)
                ->setParameter('parent', $parent);
        }

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\UrlAlias $urlAlias
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findOneWithUrlAlias(
        $urlAlias,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $txtQuery = 'SELECT n, ns, t FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.urlAliases uas
            INNER JOIN ns.translation t
            WHERE uas.id = :urlalias_id';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('urlalias_id', (int) $urlAlias->getId());

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $urlAliasAlias
     * @param AuthorizationChecker|null $authorizationChecker When not null, only PUBLISHED node
     * will be request or with a lower status
     * @param boolean $preview
     *
     * @return RZ\Roadiz\Core\Entities\Node|null
     */
    public function findOneWithAliasAndAvailableTranslation(
        $urlAliasAlias,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $txtQuery = 'SELECT n, ns, t FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.urlAliases uas
            INNER JOIN ns.translation t
            WHERE uas.alias = :alias
            AND t.available = true';

        $this->alterQueryWithAuthorizationChecker($txtQuery, $authorizationChecker, $preview);

        $query = $this->_em->createQuery($txtQuery)
            ->setParameter('alias', $urlAliasAlias);

        if (null !== $authorizationChecker) {
            $query->setParameter('status', Node::PUBLISHED);
        }

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Modify DQL query string to support node status
     * according to security context.
     *
     * A not null authorizationChecker will always filter
     * node.status to PUBLISHED or lower.
     *
     * @param  string               &$txtQuery
     * @param  AuthorizationChecker|null $authorizationChecker
     * @param  boolean $preview
     *
     * @return string
     */
    protected function alterQueryWithAuthorizationChecker(
        &$txtQuery,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $backendUser = $preview === true &&
        null !== $authorizationChecker &&
        $authorizationChecker->isGranted(Role::ROLE_BACKEND_USER);

        if ($backendUser) {
            $txtQuery .= ' AND n.status <= :status';
        } elseif (null !== $authorizationChecker) {
            $txtQuery .= ' AND n.status = :status';
        }

        return $txtQuery;
    }

    /**
     * Create a Criteria object from a search pattern and additionnal fields.
     *
     * @param string                  $pattern  Search pattern
     * @param DoctrineORMQueryBuilder $qb       QueryBuilder to pass
     * @param array                   $criteria Additionnal criteria
     * @param string                  $alias    SQL query table alias
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createSearchBy(
        $pattern,
        \Doctrine\ORM\QueryBuilder $qb,
        array $criteria = [],
        $alias = "obj"
    ) {

        $this->classicLikeComparison($pattern, $qb, $alias);

        /*
         * Handle Tag relational queries
         */
        if (isset($criteria['tags'])) {
            if (is_object($criteria['tags'])) {
                $qb->innerJoin($alias . '.tags', 'tg', Expr\Join::WITH, $qb->expr()->eq('tg.id', (int) $criteria['tags']->getId()));
            } elseif (is_array($criteria['tags'])) {
                $qb->innerJoin($alias . '.tags', 'tg', Expr\Join::WITH, $qb->expr()->in('tg.id', $criteria['tags']));
            } elseif (is_integer($criteria['tags'])) {
                $qb->innerJoin($alias . '.tags', 'tg', Expr\Join::WITH, $qb->expr()->eq('tg.id', (int) $criteria['tags']));
            }

            unset($criteria['tags']);
        }

        $qb = $this->directComparison($criteria, $qb, $alias);

        return $qb;
    }

    /**
     * @param string $nodeName
     *
     * @return boolean
     */
    public function exists($nodeName)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(n.nodeName) FROM RZ\Roadiz\Core\Entities\Node n
            WHERE n.nodeName = :node_name')
            ->setParameter('node_name', $nodeName);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node          $node
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return array
     */
    public function findByNodeAndField($node, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT n FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.aNodes ntn
            WHERE ntn.field = :field AND ntn.nodeA = :nodeA
            ORDER BY ntn.position ASC')
            ->setParameter('field', $field)
            ->setParameter('nodeA', $node);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     * @param string                      $fieldName
     *
     * @return array
     */
    public function findByNodeAndFieldName($node, $fieldName)
    {
        $query = $this->_em->createQuery('
            SELECT n FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.aNodes ntn
            INNER JOIN ntn.field f
            WHERE f.name = :name AND ntn.nodeA = :nodeA
            ORDER BY ntn.position ASC')
            ->setParameter('name', (string) $fieldName)
            ->setParameter('nodeA', $node);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Node          $node
     * @param NodeTypeField $field
     * @param Translation $translation
     *
     * @return array
     */
    public function findByNodeAndFieldAndTranslation(
        Node $node,
        NodeTypeField $field,
        Translation $translation
    ) {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.aNodes ntn
            INNER JOIN n.nodeSources ns
            WHERE ntn.field = :field
            AND ntn.nodeA = :nodeA
            AND ns.translation = :translation
            ORDER BY ntn.position ASC')
            ->setParameter('field', $field)
            ->setParameter('nodeA', $node)
            ->setParameter('translation', $translation);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Node $node
     * @param string $fieldName
     * @param Translation $translation
     *
     * @return array
     */
    public function findByNodeAndFieldNameAndTranslation(
        Node $node,
        $fieldName,
        Translation $translation
    ) {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.aNodes ntn
            INNER JOIN n.nodeSources ns
            INNER JOIN ntn.field f
            WHERE f.name = :name
            AND ntn.nodeA = :nodeA
            AND ns.translation = :translation
            ORDER BY ntn.position ASC')
            ->setParameter('name', (string) $fieldName)
            ->setParameter('nodeA', $node)
            ->setParameter('translation', $translation);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node          $node
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return array
     */
    public function findByReverseNodeAndField($node, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT n FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.bNodes ntn
            WHERE ntn.field = :field AND ntn.nodeB = :nodeB
            ORDER BY ntn.position ASC')
            ->setParameter('field', $field)
            ->setParameter('nodeB', $node);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     * @param string                      $fieldName
     *
     * @return array
     */
    public function findByReverseNodeAndFieldName($node, $fieldName)
    {
        $query = $this->_em->createQuery('
            SELECT n FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.bNodes ntn
            INNER JOIN ntn.field f
            WHERE f.name = :name AND ntn.nodeB = :nodeB
            ORDER BY ntn.position ASC')
            ->setParameter('name', (string) $fieldName)
            ->setParameter('nodeB', $node);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Node          $node
     * @param NodeTypeField $field
     * @param Translation $translation
     *
     * @return array
     */
    public function findByReverseNodeAndFieldAndTranslation(
        Node $node,
        NodeTypeField $field,
        Translation $translation
    ) {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.bNodes ntn
            INNER JOIN n.nodeSources ns
            WHERE ntn.field = :field
            AND ntn.nodeB = :nodeB
            AND ns.translation = :translation
            ORDER BY ntn.position ASC')
            ->setParameter('field', $field)
            ->setParameter('nodeB', $node)
            ->setParameter('translation', $translation);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Node $node
     * @param string $fieldName
     * @param Translation $translation
     *
     * @return array
     */
    public function findByReverseNodeAndFieldNameAndTranslation(
        Node $node,
        $fieldName,
        Translation $translation
    ) {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Roadiz\Core\Entities\Node n
            INNER JOIN n.bNodes ntn
            INNER JOIN n.nodeSources ns
            INNER JOIN ntn.field f
            WHERE f.name = :name
            AND ntn.nodeB = :nodeB
            AND ns.translation = :translation
            ORDER BY ntn.position ASC')
            ->setParameter('name', (string) $fieldName)
            ->setParameter('nodeB', $node)
            ->setParameter('translation', $translation);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return array
     */
    public function findAllOffspringIdByNode(Node $node)
    {
        $theOffprings = [];

        $in = [$node->getId()];

        do {
            $theOffprings = array_merge($theOffprings, $in);
            $query = $this->_em->createQuery('
                SELECT n.id FROM RZ\Roadiz\Core\Entities\Node n
                WHERE n.parent IN (:tab)')
                ->setParameter('tab', $in);
            $result = $query->getScalarResult();
            $in = [];

            //For memory optimizations
            foreach ($result as $item) {
                $in[] = (int) $item['id'];
            }
        } while (!empty($in));
        return $theOffprings;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return array
     */
    public function findAllParentsIdByNode(Node $node)
    {
        $theParents = [];
        $parent = $node->getParent();

        while (null !== $parent) {
            $theParents[] = $parent->getId();
            $parent = $parent->getParent();
        }

        return $theParents;
    }

    /**
     * Find all node’ parents with criteria and ordering.
     *
     * @param  Node                      $node
     * @param  array                     $criteria
     * @param  array|null                $orderBy
     * @param  integer                   $limit
     * @param  integer                   $offset
     * @param  Translation|null          $translation
     * @param  AuthorizationChecker|null $authorizationChecker
     *
     * @return array|null
     */
    public function findAllNodeParentsBy(
        Node $node,
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        AuthorizationChecker $authorizationChecker = null
    ) {

        $parentsId = $this->findAllParentsIdByNode($node);
        if (count($parentsId) > 0) {
            $criteria['id'] = $parentsId;
        } else {
            return null;
        }

        return $this->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation,
            $authorizationChecker
        );
    }
}
