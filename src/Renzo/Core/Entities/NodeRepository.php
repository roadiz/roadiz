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

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* NodeRepository
*/
class NodeRepository extends EntityRepository
{
    /**
     * @param integer                            $nodeId
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return Node or null
     */
    public function findWithTranslation($nodeId, Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.id = :nodeId AND ns.translation = :translation')
        ->setParameter('nodeId', (int) $nodeId)
        ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param integer $nodeId
     *
     * @return RZ\Renzo\Core\Entities\Node or null
     */
    public function findWithDefaultTranslation($nodeId)
    {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.id = :nodeId AND t.defaultTranslation = 1')
        ->setParameter('nodeId', (int) $nodeId);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string                             $nodeName
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return RZ\Renzo\Core\Entities\Node or null
     */
    public function findByNodeNameWithTranslation($nodeName, Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.nodeName = :nodeName AND ns.translation = :translation')
        ->setParameter('nodeName', $nodeName)
        ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $nodeName
     *
     * @return RZ\Renzo\Core\Entities\Node or null
     */
    public function findByNodeNameWithDefaultTranslation($nodeName)
    {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.nodeName = :nodeName AND t.defaultTranslation = 1')
        ->setParameter('nodeName', $nodeName);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Node        $node
     * @param Translation $translation
     *
     * @return RZ\Renzo\Core\Entities\Node or null
     */
    public function getChildrenWithTranslation(Node $node, Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            WHERE n.parent = :node AND ns.translation = :translation')
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
     *
     * @return array Doctrine result array
     */
    public function findByParentWithTranslation(Translation $translation, Node $parent = null)
    {
        $query = null;

        if ($parent === null) {
            $query = $this->_em->createQuery('
                SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
                INNER JOIN n.nodeSources ns
                INNER JOIN ns.translation t
                WHERE n.parent IS NULL AND t.id = :translation_id
                ORDER BY n.position ASC')
            ->setParameter('translation_id', (int) $translation->getId());
        } else {
            $query = $this->_em->createQuery('
                SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
                INNER JOIN n.nodeSources ns
                INNER JOIN ns.translation t
                INNER JOIN n.parent pn
                WHERE pn.id = :parent AND t.id = :translation_id
                ORDER BY n.position ASC')
            ->setParameter('parent', $parent->getId())
            ->setParameter('translation_id', (int) $translation->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Node        $parent
     *
     * @return array Doctrine result array
     */
    public function findByParentWithDefaultTranslation(Node $parent = null)
    {
        $query = null;
        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.parent IS NULL AND t.defaultTranslation = 1
            ORDER BY n.position ASC');
        } else {
            $query = $this->_em->createQuery('
                SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
                INNER JOIN n.nodeSources ns
                INNER JOIN ns.translation t
                INNER JOIN n.parent pn
                WHERE pn.id = :parent AND t.defaultTranslation = 1
                ORDER BY n.position ASC')
            ->setParameter('parent', $parent->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\UrlAlias $urlAlias
     *
     * @return Node or null
     */
    public function findOneWithUrlAlias($urlAlias)
    {
        $query = $this->_em->createQuery('
            SELECT n, ns, t FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.urlAliases uas
            INNER JOIN ns.translation t
            WHERE uas.id = :urlalias_id')
        ->setParameter('urlalias_id', (int) $urlAlias->getId());

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
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
