<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

/**
* 
*/
class NodeRepository extends EntityRepository
{
	/**
     * 
     * @param  array  $args        Node filter array
     * @param  array  $order       Node ordering array
     * @param  array  $sourceArgs  Source filter array
     * @param  array  $sourceOrder Source ordering array
     * @return array
     */
	public function findWithSourceBy( $args, $order = array(), $sourceArgs = array(), $sourceOrder = array() )
    {
    	$nodes = $this->findBy($args, $order);

    	foreach ($nodes as $index => $node) {

            $nodeSource = $node->getDefaultNodeSource();
            if ($nodeSource !== null) {
                $sourceArgs['id'] = $nodeSource->getSourceId();
        		$source = $this->_em
                    ->getRepository('GeneratedNodeSources\\'.$node->getNodeType()->getSourceEntityClassName())
        			->findOneBy($sourceArgs, $sourceOrder);

                if ($source !== null) {
                    $node->setSource( $source );
                }
                else {
                    unset($nodes[$index]);
                }
            }
    	}
        return $nodes;
    }

    /**
     * 
     * @param  array  $args        Node filter array
     * @param  array  $order       Node ordering array
     * @param  array  $sourceArgs  Source filter array
     * @param  array  $sourceOrder Source ordering array
     * @return RZ\Renzo\Core\Entities\Node
     */
    public function findOneWithSourceBy( $args, $order = array(), $sourceArgs = array(), $sourceOrder = array() )
    {
        $node = $this->findOneBy($args, $order);

        if ($node !== null) {
            $nodeSource = $node->getDefaultNodeSource();
            if ($nodeSource !== null) {

                $sourceArgs['id'] = $nodeSource->getSourceId();

                $source = $this->_em
                    ->getRepository('GeneratedNodeSources\\'.$node->getNodeType()->getSourceEntityClassName())
                    ->findOneBy($sourceArgs, $sourceOrder);

                if ($source !== null) {
                    $node->setSource( $source );
                }
                else {
                    return null;
                }
            }
        }

        return $node;
    }
}