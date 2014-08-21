<?php 

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Utils\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Kernel;

class SettingRepository extends EntityRepository
{
	/**
	 * 
	 * @param  string $name
	 * @return array     
	 */
	public function getValue($name)
	{
		$query = $this->_em->createQuery('
			SELECT s.value FROM RZ\Renzo\Core\Entities\Setting s 
			WHERE s.name = :name'
						)->setParameter('name', $name);

		try {
			return $query->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/**
	 * 
	 * @param  string $name 
	 * @return array
	 */
	public function exists($name)
	{
		$query = $this->_em->createQuery('
			SELECT COUNT(s.value) FROM RZ\Renzo\Core\Entities\Setting s 
			WHERE s.name = :name'
						)->setParameter('name', $name);

		try {
			return (boolean)$query->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}
}