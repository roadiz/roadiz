<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file DocumentRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Repositories;

use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
 * {@inheritdoc}
 */
class DocumentRepository extends EntityRepository
{
    /**
     * Add a folder filtering to queryBuilder.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByFolder(&$criteria, &$qb)
    {
        if (in_array('folders', array_keys($criteria))) {
            if (is_array($criteria['folders'])) {
                if (in_array("folderExclusive", array_keys($criteria))
                    && $criteria["folderExclusive"] == true) {
                    $documents = static::getDocumentIdsByFolderExcl($criteria['folders']);
                    $criteria["id"] = $documents;
                    unset($criteria["folderExclusive"]);
                    unset($criteria['folders']);
                } else {
                    $qb->innerJoin(
                        'd.folders',
                        'fd',
                        'WITH',
                        'fd.id IN (:folders)'
                    );
                }
            } else {
                $qb->innerJoin(
                    'n.folders',
                    'fd',
                    'WITH',
                    'fd.id = :folders'
                );
            }
        }
    }

    /**
     * Seach DocumentId exclusively
     *
     * @param  array     $folders
     * @return array
     */

    public static function getDocumentIdsByFolderExcl($folders)
    {
        $qb = Kernel::getInstance()->getService('em')->createQueryBuilder();

        $qb->select("d.id")
           ->addSelect("COUNT(fd.id) as num")
           ->from("RZ\Renzo\Core\Entities\Folder", "fd")
           ->leftJoin("fd.documents", "d");
        foreach ($folders as $key => $folder) {
            $qb->orWhere($qb->expr()->eq('fd.id', ':folder'.$key));
        }
        $qb->groupBy("d.id");
        $query = $qb->getQuery();
        foreach ($folders as $key => $folder) {
            $query->setParameter("folder".$key, $folder);
        }
        $results = $query->getResult();
        $count = count($folders);
        $documents = array();
        foreach ($results as $key => $result) {
            if ($count === (int) $result["num"]) {
                $documents[] = $result["id"];
            }
        }
        return $documents;
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

            if ($key == "folders" || $key == "folderExclusive") {
                continue;
            }

            /*
             * compute prefix for
             * filtering node, and sources relation fields
             */
            $prefix = 'd.';

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
                $prefix = 'dt.';
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
                        case 'NOT IN':
                            $res = $qb->expr()->notIn($prefix.$key, ':'.$baseKey);
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

            if ($key == "folders" || $key == "folderExclusive") {
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
                        case 'NOT IN':
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
    protected function applyFilterByFolder(array &$criteria, &$finalQuery)
    {
        if (in_array('folders', array_keys($criteria))) {
            if (is_object($criteria['folders'])) {
                $finalQuery->setParameter('folders', $criteria['folders']->getId());
            } elseif (is_array($criteria['folders'])) {
                $finalQuery->setParameter('folders', $criteria['folders']);
            } elseif (is_integer($criteria['folders'])) {
                $finalQuery->setParameter('folders', (int) $criteria['folders']);
            }
            unset($criteria["folders"]);
        }
    }

    /**
     * Bind translation parameter to final query
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyTranslationByFolder(
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

            $qb->innerJoin('d.documentTranslations', 'dt');
            $qb->innerJoin('dt.translation', 't');

        } else {

            if (null !== $translation) {
                /*
                 * With a given translation
                 */
                $qb->innerJoin(
                    'd.documentTranslations',
                    'dt',
                    'WITH',
                    'dt.translation = :translation'
                );
            } else {
                /*
                 * With a null translation, just take the default one.
                 */
                $qb->innerJoin('d.documentTranslations', 'dt');
                $qb->innerJoin(
                    'dt.translation',
                    't',
                    'WITH',
                    't.defaultTranslation = true'
                );
            }
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
        array &$criteria,
        array &$orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null
    ) {

        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'd')
           ->add('from', $this->getEntityName() . ' d');

        //$this->filterByTranslation($criteria, $qb, $translation);

        /*
         * Filtering by tag
         */
        $this->filterByFolder($criteria, $qb);
        $this->filterByCriteria($criteria, $qb);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy('d.'.$key, $value);
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
        array &$criteria,
        Translation $translation = null
    ) {

        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'count(d.id)')
           ->add('from', $this->getEntityName() . ' d');

        //$this->filterByTranslation($criteria, $qb, $translation);
        /*
         * Filtering by tag
         */
        $this->filterByFolder($criteria, $qb);
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
        Translation $translation = null
    ) {
        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation
        );

        $finalQuery = $query->getQuery();

        //var_dump($finalQuery->getDql()); exit();

        $this->applyFilterByFolder($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        //$this->applyTranslationByFolder($criteria, $finalQuery, $translation);

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
        Translation $translation = null
    ) {

        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            1,
            0,
            $translation
        );

        $finalQuery = $query->getQuery();

        $this->applyFilterByFolder($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        //$this->applyTranslationByFolder($criteria, $finalQuery, $translation);

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
        Translation $translation = null
    ) {
        $query = $this->getCountContextualQueryWithTranslation(
            $criteria,
            $translation
        );

        $finalQuery = $query->getQuery();
        $this->applyFilterByFolder($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        //$this->applyTranslationByFolder($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }


    /**
     * @param RZ\Renzo\Core\Entities\NodesSources  $nodeSource
     * @param RZ\Renzo\Core\Entities\NodeTypeField $field
     *
     * @return array
     */
    public function findByNodeSourceAndField(
        $nodeSource,
        NodeTypeField $field
    ) {
        $query = $this->_em->createQuery('
            SELECT d, dt FROM RZ\Renzo\Core\Entities\Document d
            INNER JOIN d.documentTranslations dt
            INNER JOIN d.nodesSourcesByFields nsf
            WHERE nsf.field = :field
            AND nsf.nodeSource = :nodeSource
            AND dt.translation = :translation
            ORDER BY nsf.position ASC')
                        ->setParameter('field', $field)
                        ->setParameter('nodeSource', $nodeSource)
                        ->setParameter('translation', $nodeSource->getTranslation());
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\NodesSources $nodeSource
     * @param string                              $fieldName
     *
     * @return array
     */
    public function findByNodeSourceAndFieldName(
        $nodeSource,
        $fieldName
    ) {
        $query = $this->_em->createQuery('
            SELECT d, dt FROM RZ\Renzo\Core\Entities\Document d
            INNER JOIN d.documentTranslations dt
            INNER JOIN d.nodesSourcesByFields nsf
            INNER JOIN nsf.field f
            WHERE f.name = :name
            AND nsf.nodeSource = :nodeSource
            AND dt.translation = :translation
            ORDER BY nsf.position ASC')
                        ->setParameter('name', (string) $fieldName)
                        ->setParameter('nodeSource', $nodeSource)
                        ->setParameter('translation', $nodeSource->getTranslation());
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
