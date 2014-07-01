<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Kernel;

class SettingRepository extends EntityRepository
{
	public function getValue($name)
	{
		$query = Kernel::getInstance()->em()
						->createQuery('
			SELECT s.value FROM RZ\Renzo\Core\Entities\Setting s 
			WHERE s.name = :name'
						)->setParameter('name', $name);

		try {
			return $query->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}
}