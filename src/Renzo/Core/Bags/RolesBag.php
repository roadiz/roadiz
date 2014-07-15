<?php 

namespace RZ\Renzo\Core\Bags;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\core\Entities\Role;

abstract class RolesBag
{
	/**
	 * Cached roles values
	 * @var array
	 */
	private static $roles = array();

	/**
	 * Get role by name or create it if non-existant
	 * @param  string $roleName
	 * @return RZ\Renzo\core\Entities\Role
	 */
	public static function get( $roleName )
	{
		if (!isset(static::$roles[$roleName])) {
			static::$roles[$roleName] = 
					Kernel::getInstance()->em()
            		->getRepository('RZ\Renzo\Core\Entities\Role')
            		->findOneBy(array('name'=>$roleName));

            if (static::$roles[$roleName] === null) {
            	static::$roles[$roleName] = new Role();

            	static::$roles[$roleName]->setName($roleName);
            	Kernel::getInstance()->em()->persist(static::$roles[$roleName]);
            	Kernel::getInstance()->em()->flush();
            }
		}

		return static::$roles[$roleName];
	}
}