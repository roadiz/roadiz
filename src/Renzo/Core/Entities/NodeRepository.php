<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class NodeRepository extends EntityRepository
{	
	/**
	 * 
	 * @param  integer      $node_id     [description]
	 * @param  Translation $translation [description]
	 * @return Node or null
	 */
	public function findWithTranslation($node_id, Translation $translation )
	{
	    $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            WHERE n.id = :node_id AND ns.translation = :translation'
                        )->setParameter('node_id', (int)$node_id)
                        ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

    /**
     * 
     * @param  integer      $node_id     [description]
     * @return Node or null
     */
    public function findWithDefaultTranslation($node_id)
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            INNER JOIN ns.translation t
            WHERE n.id = :node_id AND t.defaultTranslation = :defaultTranslation'
                        )->setParameter('node_id', (int)$node_id)
                        ->setParameter('defaultTranslation', true);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * [findByParentWithTranslation description]
     * @param  Node        $parent      [description]
     * @param  Translation $translation [description]
     * @return array Doctrine result array
     */
    public function findByParentWithTranslation( Node $parent = null, Translation $translation )
    {
        $query = null;

        if ($parent === null) {
            $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            INNER JOIN ns.translation t
            WHERE n.parent IS NULL AND t.id = :translation_id
            ORDER BY n.position ASC'
                        )->setParameter('translation_id', (int)$translation->getId());
        }
        else {
            $query = Kernel::getInstance()->em()
                            ->createQuery('
                SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
                INNER JOIN n.nodeSources ns 
                INNER JOIN ns.translation t
                INNER JOIN n.parent pn
                WHERE pn.id = :parent AND t.id = :translation_id
                ORDER BY n.position ASC'
                            )->setParameter('parent', $parent->getId())
                            ->setParameter('translation_id', (int)$translation->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * 
     * @param  Node        $parent      [description]
     * @param  Translation $translation [description]
     * @return array Doctrine result array
     */
    public function findByParentWithDefaultTranslation( Node $parent = null )
    {
        $query = null;
        if ($parent === null) {
            $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            INNER JOIN ns.translation t
            WHERE n.parent IS NULL AND t.defaultTranslation = :defaultTranslation
            ORDER BY n.position ASC'
                        )->setParameter('defaultTranslation', true);
        }
        else {
            $query = Kernel::getInstance()->em()
                            ->createQuery('
                SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
                INNER JOIN n.nodeSources ns 
                INNER JOIN ns.translation t
                INNER JOIN n.parent pn
                WHERE pn.id = :parent AND t.defaultTranslation = :defaultTranslation
                ORDER BY n.position ASC'
                            )->setParameter('parent', $parent->getId())
                            ->setParameter('defaultTranslation', true);
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * 
     * @param  \RZ\Renzo\Core\Entities\UrlAlias $urlAlias 
     * @return Node or null
     */
    public function findOneWithUrlAlias( $urlAlias )
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT n, ns, t FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            INNER JOIN ns.urlAliases uas
            INNER JOIN ns.translation t
            WHERE uas.id = :urlalias_id'
                        )->setParameter('urlalias_id', (int)$urlAlias->getId());

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * 
     * @param  string $alias
     * @return boolean
     */
    public function exists( $nodeName )
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT COUNT(n.nodeName) FROM RZ\Renzo\Core\Entities\Node n 
            WHERE n.nodeName = :node_name
        ')->setParameter('node_name', $nodeName);

        try {
            return (boolean)$query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}