<?php 
namespace RZ\Renzo\Core\Utils;

use Doctrine\Common\Collections\Criteria;
/**
 * EntityRepository that implements a simple countBy method.
 * 
 */
class EntityRepository extends \Doctrine\ORM\EntityRepository
{
	
	/**
     * Count entities using a Criteria object or a simple filter array.
     * 
     * @param  Doctrine\Common\Collections\Criteria or array
     * @return integer
     */
    public function countBy( $criteria )
    {   
        if ( $criteria instanceof Criteria ) {
            $collection = $this->matching($criteria);
            return $collection->count();
        }
        elseif (is_array($criteria)) {
            $expr = Criteria::expr();
            $criteriaObj = Criteria::create();

            $i = 0;
            foreach ($criteria as $key => $value) {

                if (is_array($value)) {
                    $res = $expr->in($key, $value);
                }
                else {
                    $res = $expr->eq($key, $value);
                }


                if ($i == 0) {
                    $criteriaObj->where($res);
                }
                else {
                    $criteriaObj->andWhere($res);
                }

                $i++;
            }

            $collection = $this->matching($criteriaObj);
            return $collection->count();
        }
    }
}