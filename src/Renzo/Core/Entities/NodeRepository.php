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
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Common\Collections\ArrayCollection;

/**
* NodeRepository
*/
class NodeRepository extends EntityRepository
{
    /**
     * A secure findBy with which user must be a backend user
     * to see unpublished nodes.
     *
     * @param SecurityContext $securityContext
     * @param array           $criteria
     * @param array           $orderBy
     * @param integer         $limit
     * @param integer         $offset
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function contextualFindBy(
        SecurityContext $securityContext,
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {

        if (!$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $criteria['published'] = true;
        }

        return parent::findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
    }

    /**
     * A secure findOneBy with which user must be a backend user
     * to see unpublished nodes.
     *
     * @param SecurityContext $securityContext
     * @param array           $criteria
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function contextualFindOneBy(SecurityContext $securityContext, array $criteria)
    {

        if (!$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $criteria['published'] = true;
        }

        return parent::findOneBy($criteria);
    }

    /**
     * @param integer                            $nodeId
     * @param RZ\Renzo\Core\Entities\Translation $translation
     * @param SecurityContext|null               $securityContext
     *
     * @return Node or null
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
            $txtQuery .= ' AND n.published = true';
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
     * @param integer              $nodeId
     * @param SecurityContext|null $securityContext
     *
     * @return RZ\Renzo\Core\Entities\Node|null
     */
    public function findWithDefaultTranslation($nodeId, SecurityContext $securityContext = null)
    {

        $txtQuery = 'SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n
            INNER JOIN n.nodeSources ns
            INNER JOIN ns.translation t
            WHERE n.id = :nodeId AND t.defaultTranslation = true';

        if (null !== $securityContext &&
            !$securityContext->isGranted(Role::ROLE_BACKEND_USER)) {
            $txtQuery .= ' AND n.published = true';
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
            $txtQuery .= ' AND n.published = true';
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
            $txtQuery .= ' AND n.published = true';
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
     * @param Node                 $node
     * @param Translation          $translation
     * @param SecurityContext|null $securityContext
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
            $txtQuery .= ' AND n.published = true';
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
            $txtQuery .= ' AND n.published = true';
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
            $txtQuery .= ' AND n.published = true';
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
     * @return Node|null
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
            $txtQuery .= ' AND n.published = true';
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
