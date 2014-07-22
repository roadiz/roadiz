<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class DocumentRepository extends EntityRepository
{	

    /**
     * 
     * @param  RZ\Renzo\Core\Entities\NodesSources $nodeSource
     * @param  RZ\Renzo\Core\Entities\NodeTypeField $field
     * @return array
     */
    public function findByNodeSourceAndField( $nodeSource, NodeTypeField $field )
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT d FROM RZ\Renzo\Core\Entities\Document d 
            INNER JOIN d.nodesSourcesByFields nsf
            WHERE nsf.field = :field AND nsf.nodeSource = :nodeSource
            ORDER BY nsf.position ASC')
                        ->setParameter('field', $field)
                        ->setParameter('nodeSource',$NodesSources);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * 
     * @param  RZ\Renzo\Core\Entities\NodesSources $nodeSource
     * @param  string $fieldName
     * @return array
     */
    public function findByNodeSourceAndFieldName( $nodeSource, $fieldName )
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT d FROM RZ\Renzo\Core\Entities\Document d 
            INNER JOIN d.nodesSourcesByFields nsf
            INNER JOIN nsf.field f
            WHERE f.name = :name AND nsf.nodeSource = :nodeSource
            ORDER BY nsf.position ASC')
                        ->setParameter('name', (string)$fieldName)
                        ->setParameter('nodeSource', $nodeSource);
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}