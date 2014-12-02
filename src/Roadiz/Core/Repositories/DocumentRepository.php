<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file DocumentRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use Doctrine\Common\Collections\ArrayCollection;

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
                    && $criteria["folderExclusive"] === true) {
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
           ->from("RZ\Roadiz\Core\Entities\Folder", "fd")
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
             * Search in translation fields
             */
            if (false !== strpos($key, 'documentTranslations.')) {
                $prefix = 'dt.';
                $key = str_replace('documentTranslations.', '', $key);
            }

            if ($key == 'translation') {
                $prefix = 'dt.';
            }

            $qb->andWhere($this->buildComparison($value, $prefix, $key, $baseKey, $qb));
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

        /*
         * Search in document fields
         */
        $criteriaFields = array();
        $metadatas = $this->_em->getClassMetadata($this->getEntityName());
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $types)) {
                $criteriaFields[$field] = '%'.strip_tags($pattern).'%';
            }
        }
        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like($alias . '.' .$key, $qb->expr()->literal($value)));
        }

        /*
         * Search in translations
         */
        $qb->leftJoin('obj.documentTranslations', 'dt');
        $criteriaFields = array();
        $metadatas = $this->_em->getClassMetadata('RZ\Roadiz\Core\Entities\DocumentTranslation');
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $types)) {
                $criteriaFields[$field] = '%'.strip_tags($pattern).'%';
            }
        }
        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like('dt.' .$key, $qb->expr()->literal($value)));
        }

        foreach ($criteria as $key => $value) {
            if (is_object($value) && $value instanceof PersistableInterface) {
                $res = $qb->expr()->eq($alias . '.' .$key, $value->getId());
            } elseif (is_array($value)) {
                $res = $qb->expr()->in($alias . '.' .$key, $value);
            } elseif (is_bool($value)) {
                $res = $qb->expr()->eq($alias . '.' .$key, (int) $value);
            } else {
                $res = $qb->expr()->eq($alias . '.' .$key, $value);
            }

            $qb->andWhere($res);
        }

        return $qb;
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
     * @param RZ\Roadiz\Core\Entities\Translation|null $securityContext
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
     * @param RZ\Roadiz\Core\Entities\Translation|null $securityContext
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
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
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
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
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
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
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
     * @param RZ\Roadiz\Core\Entities\NodesSources  $nodeSource
     * @param RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return array
     */
    public function findByNodeSourceAndField(
        $nodeSource,
        NodeTypeField $field
    ) {
        $query = $this->_em->createQuery('
            SELECT d, dt FROM RZ\Roadiz\Core\Entities\Document d
            LEFT JOIN d.documentTranslations dt
                WITH dt.translation = :translation
            INNER JOIN d.nodesSourcesByFields nsf
                WITH nsf.nodeSource = :nodeSource
            WHERE nsf.field = :field
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
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     * @param string                              $fieldName
     *
     * @return array
     */
    public function findByNodeSourceAndFieldName(
        $nodeSource,
        $fieldName
    ) {
        $query = $this->_em->createQuery('
            SELECT d, dt FROM RZ\Roadiz\Core\Entities\Document d
            LEFT JOIN d.documentTranslations dt
                WITH dt.translation = :translation
            INNER JOIN d.nodesSourcesByFields nsf
                WITH nsf.nodeSource = :nodeSource
            INNER JOIN nsf.field f
                WITH f.name = :name
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
