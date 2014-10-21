<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file NodeRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\Repositories;

use \RZ\Renzo\Core\AbstractEntities\PersistableInterface;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr;

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
                if (!in_array("tag_exclusive", array_keys($criteria))) {
                    $qb->innerJoin(
                        'n.tags',
                        'tg',
                        'WITH',
                        'tg.id IN (:tags)'
                    );
                } else {
                    $qb->innerJoin(
                        'n.tags',
                        'tg'
                    );
                    foreach ($criteria['tags'] as $key => $tag) {
                        $ql->addWhere($qb->expr()->eq("tg.id", ':tag'.$key));
                    }
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
     * Reimplementing findBy features… with extra things
     *
     * * key => array('<=', $value)
     * * key => array('<', $value)
     * * key => array('>=', $value)
     * * key => array('>', $value)
     * * key => array('BETWEEN', $value, $value)
     * * key => array('LIKE', $value)
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

            if ($key == "tags") {
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

            if (is_object($value) && $value instanceof PersistableInterface) {
                $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
            } elseif (is_array($value)) {
                /*
                 * array
                 *
                 * ['<=', $value]
                 * ['<', $value]
                 * ['>=', $value]
                 * ['>', $value]
                 * ['BETWEEN', $value, $value]
                 * ['LIKE', $value]
                 * in [$value, $value]
                 */
                if (count($value) > 1) {
                    switch ($value[0]) {
                        case '<=':
                            # lte
                            $res = $qb->expr()->lte($prefix.$key, ':'.$baseKey);
                            break;
                        case '<':
                            # lt
                            $res = $qb->expr()->lt($prefix.$key, ':'.$baseKey);
                            break;
                        case '>=':
                            # gte
                            $res = $qb->expr()->gte($prefix.$key, ':'.$baseKey);
                            break;
                        case '>':
                            # gt
                            $res = $qb->expr()->gt($prefix.$key, ':'.$baseKey);
                            break;
                        case 'BETWEEN':
                            $res = $qb->expr()->between(
                                $prefix.$key,
                                ':'.$baseKey.'_1',
                                ':'.$baseKey.'_2'
                            );
                            break;
                        case 'LIKE':
                            $res = $qb->expr()->like($prefix.$key, $qb->expr()->literal($value[1]));
                            break;
                        default:
                            $res = $qb->expr()->in($prefix.$key, ':'.$baseKey);
                            break;
                    }
                } else {
                    $res = $qb->expr()->in($prefix.$key, ':'.$baseKey);
                }

            } elseif (is_bool($value)) {
                $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
            } elseif ('NOT NULL' == $value) {
                $res = $qb->expr()->isNotNull($prefix.$key);
            } elseif (isset($value)) {
                $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
            } elseif (null === $value) {
                $res = $qb->expr()->isNull($prefix.$key);
            }

            $qb->andWhere($res);
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

            if ($key == "tags") {
                continue;
            }

            // Dots are forbidden in field definitions
            $key = str_replace('.', '_', $key);

            if (is_object($value) && $value instanceof PersistableInterface) {
                $finalQuery->setParameter($key, $value->getId());
            } elseif (is_array($value)) {

                if (count($value) > 1) {
                    switch ($value[0]) {
                        case '<=':
                        case '<':
                        case '>=':
                        case '>':
                            $finalQuery->setParameter($key, $value[1]);
                            break;
                        case 'BETWEEN':
                            $finalQuery->setParameter($key.'_1', $value[1]);
                            $finalQuery->setParameter($key.'_2', $value[2]);
                            break;
                        case 'LIKE':
                            // param is setted in filterBy
                            break;
                        default:
                            $finalQuery->setParameter($key, $value);
                            break;
                    }
                } else {
                    $finalQuery->setParameter($key, $value);
                }

            } elseif (is_bool($value)) {
                $finalQuery->setParameter($key, $value);
            } elseif ('NOT NULL' == $value) {
                // param is not needed
            } elseif (isset($value)) {
                $finalQuery->setParameter($key, $value);
            } elseif (null === $value) {
                // param is not needed
            }
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
                if (!in_array("tag_exclusive", array_keys($criteria))) {
                    $finalQuery->setParameter('tags', $criteria['tags']->getId());
                } else {
                    foreach ($criteria['tags'] as $key => $tag) {
                        $finalQuery->setParameter(':tag'.$key, $tag->getId());
                    }
                    unset($criteria["tag_exclusive"]);
                }
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
            isset($criteria['translation.id'])) {

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
                 * With a null translation, just take the default one.
                 */
                $qb->innerJoin('n.nodeSources', 'ns');
                $qb->innerJoin(
                    'ns.translation',
                    't',
                    'WITH',
                    't.defaultTranslation = true'
                );
            }
        }
    }

    protected function filterBySecurityContext(&$criteria, &$qb, &$securityContext = null)
    {
        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            /*
             * Forbid unpublished node for anonymous and not backend users.
             */
            $qb->andWhere($qb->expr()->eq('n.status', Node::PUBLISHED));
        }
    }

    /**
     * Create a securized query with node.published = true if user is
     * not a Backend user and if securityContext is defined.
     *
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param RZ\Renzo\Core\Entities\Translation|null $securityContext
     * @param SecurityContext|null                    $securityContext
     *
     * @return QueryBuilder
     */
    protected function getContextualQueryWithTranslation(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        SecurityContext $securityContext = null
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
        $this->filterBySecurityContext($criteria, $qb, $securityContext);


        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy('n.'.$key, $value);
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
     * not a Backend user and if securityContext is defined.
     *
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array                                   $criteria
     * @param RZ\Renzo\Core\Entities\Translation|null $securityContext
     * @param SecurityContext|null                    $securityContext
     *
     * @return QueryBuilder
     */
    protected function getCountContextualQueryWithTranslation(
        array $criteria,
        Translation $translation = null,
        SecurityContext $securityContext = null
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
        $this->filterBySecurityContext($criteria, $qb, $securityContext);

        return $qb;
    }
    /**
     * Just like the findBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param RZ\Renzo\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {
        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation,
            $securityContext
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
     * @param RZ\Renzo\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {

        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            1,
            0,
            $translation,
            $securityContext
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
     * @param RZ\Renzo\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return int
     */
    public function countBy(
        $criteria,
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {
        $query = $this->getCountContextualQueryWithTranslation(
            $criteria,
            $translation,
            $securityContext
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
     * SecurityContext.
     *
     * If no translation nor securityContext is given, the vanilla `findBy`
     * method will be called instead.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param RZ\Renzo\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByWithTranslation(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {
        return $this->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation,
            $securityContext
        );
    }

    /**
     * Just like the findOneBy method but with a given Translation and optional
     * SecurityContext.
     *
     * If no translation nor securityContext is given, the vanilla `findOneBy`
     * method will be called instead.
     *
     * @param array                                   $criteria
     * @param RZ\Renzo\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findOneByWithTranslation(
        array $criteria,
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {
        return $this->findOneBy(
            $criteria,
            null,
            $translation,
            $securityContext
        );
    }

    /**
     * Find one Node with its Id and a given translation.
     *
     * @param integer                            $nodeId
     * @param RZ\Renzo\Core\Entities\Translation $translation
     * @param SecurityContext|null               $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findWithTranslation(
        $nodeId,
        Translation $translation,
        SecurityContext $securityContext = null
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.id = :nodeId AND ns.translation = :translation';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery)
                           ->setParameter('nodeId', (int) $nodeId)
                           ->setParameter('translation', $translation);

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
     * @param SecurityContext|null $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findWithDefaultTranslation(
        $nodeId,
        SecurityContext $securityContext = null
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.id = :nodeId AND t.defaultTranslation = true';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery)
                           ->setParameter('nodeId', (int) $nodeId);

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
     * @param RZ\Renzo\Core\Entities\Translation $translation
     * @param SecurityContext|null               $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findByNodeNameWithTranslation(
        $nodeName,
        Translation $translation,
        SecurityContext $securityContext = null
    ) {
        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.nodeName = :nodeName AND ns.translation = :translation';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery)
                           ->setParameter('nodeName', $nodeName)
                           ->setParameter('translation', $translation);

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
     * @param SecurityContext|null $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findByNodeNameWithDefaultTranslation(
        $nodeName,
        SecurityContext $securityContext = null
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.nodeName = :nodeName AND t.defaultTranslation = true';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery)
                           ->setParameter('nodeName', $nodeName);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find the Home node with a given translation.
     *
     * @param RZ\Renzo\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findHomeWithTranslation(
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {

        if (null === $translation) {
            return $this->findHomeWithDefaultTranslation($securityContext);
        }

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.home = true AND ns.translation = :translation';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery)
                           ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find the Home node with the default translation.
     *
     * @param SecurityContext|null $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findHomeWithDefaultTranslation(
        SecurityContext $securityContext = null
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.home = true AND t.defaultTranslation = true';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Node        $node
     * @param RZ\Renzo\Core\Entities\Translation $translation
     * @param SecurityContext|null               $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildrenWithTranslation(
        Node $node,
        Translation $translation,
        SecurityContext $securityContext = null
    ) {

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.parent = :node AND ns.translation = :translation';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery)
                           ->setParameter('node', $node)
                           ->setParameter('translation', $translation);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     * @param RZ\Renzo\Core\Entities\Node        $parent
     * @param SecurityContext|null               $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByParentWithTranslation(
        Translation $translation,
        Node $parent = null,
        SecurityContext $securityContext = null
    ) {
        $query = null;

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
                     INNER JOIN n.nodeSources ns
                     INNER JOIN ns.translation t';

        if ($parent === null) {
            $txtQuery .= PHP_EOL.'WHERE n.parent IS NULL';
        } else {
            $txtQuery .= PHP_EOL.'WHERE n.parent = :parent';
        }

        $txtQuery .= ' AND t.id = :translation_id';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $txtQuery .= ' ORDER BY n.position ASC';

        if ($parent === null) {
            $query = $this->_em->createQuery($txtQuery)
                               ->setParameter('translation_id', (int) $translation->getId());
        } else {
            $query = $this->_em->createQuery($txtQuery)
                               ->setParameter('parent', $parent)
                               ->setParameter('translation_id', (int) $translation->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Node $parent
     * @param SecurityContext|null        $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findByParentWithDefaultTranslation(
        Node $parent = null,
        SecurityContext $securityContext = null
    ) {
        $query = null;

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
                     INNER JOIN n.nodeSources ns
                     INNER JOIN ns.translation t';

        if ($parent === null) {
            $txtQuery .= PHP_EOL.'WHERE n.parent IS NULL';
        } else {
            $txtQuery .= PHP_EOL.'WHERE n.parent = :parent';
        }

        $txtQuery .= ' AND t.defaultTranslation = true';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $txtQuery .= ' ORDER BY n.position ASC';

        if ($parent === null) {
            $query = $this->_em->createQuery($txtQuery);
        } else {
            $query = $this->_em->createQuery($txtQuery)
                               ->setParameter('parent', $parent);
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\UrlAlias $urlAlias
     * @param SecurityContext|null            $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findOneWithUrlAlias($urlAlias, SecurityContext $securityContext = null)
    {
        $txtQuery = 'SELECT n, ns, t FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.urlAliases uas
            INNER JOIN ns.translation t
            WHERE uas.id = :urlalias_id';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.status = \''.Node::PUBLISHED.'\'';
        }

        $query = $this->_em->createQuery($txtQuery)
                           ->setParameter('urlalias_id', (int) $urlAlias->getId());

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
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
        array $criteria = array(),
        $alias = "obj"
    ) {
        /*
         * get fields needed for a search
         * query
         */
        $types = array('string', 'text');
        $criteriaFields = array();
        $cols = $this->_em->getClassMetadata($this->getEntityName())->getColumnNames();
        foreach ($cols as $col) {
            $field = $this->_em->getClassMetadata($this->getEntityName())->getFieldName($col);
            $type = $this->_em->getClassMetadata($this->getEntityName())->getTypeOfField($field);

            if (in_array($type, $types)) {
                $criteriaFields[$this->_em->getClassMetadata($this->getEntityName())->getFieldName($col)] =
                    '%'.strip_tags($pattern).'%';
            }
        }

        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like($alias . '.' .$key, $qb->expr()->literal($value)));
        }

        /*
         * Handle Tag relational queries
         */
        if (isset($criteria['tags'])) {
            if (is_object($criteria['tags'])) {
                $qb->innerJoin($alias.'.tags', 'tg', Expr\Join::WITH, $qb->expr()->eq('tg.id', (int) $criteria['tags']->getId()));
            } elseif (is_array($criteria['tags'])) {
                $qb->innerJoin($alias.'.tags', 'tg', Expr\Join::WITH, $qb->expr()->in('tg.id', $criteria['tags']));
            } elseif (is_integer($criteria['tags'])) {
                $qb->innerJoin($alias.'.tags', 'tg', Expr\Join::WITH, $qb->expr()->eq('tg.id', (int) $criteria['tags']));
            }

            unset($criteria['tags']);
        }

        foreach ($criteria as $key => $value) {

            if (is_array($value)) {
                $res = $qb->expr()->in($alias . '.' .$key, $value);
            } elseif (is_bool($value)) {
                $res = $qb->expr()->eq($alias . '.' .$key, (boolean) $value);
            } else {
                $res = $qb->expr()->eq($alias . '.' .$key, $value);
            }

            $qb->andWhere($res);
        }

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
            SELECT COUNT(n.nodeName) FROM RZ\Renzo\Core\Entities\Node n
            WHERE n.nodeName = :node_name')
            ->setParameter('node_name', $nodeName);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}
