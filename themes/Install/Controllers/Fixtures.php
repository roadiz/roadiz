<?php 

namespace Themes\Install\Controllers;


use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\User;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Entities\Setting;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
/**
* 
*/
class Fixtures {

	public function installFixtures()
	{
		$this->createFolders();
		$this->installDefaultTranslation();
		$this->installBackofficeTheme();

		Kernel::getInstance()->em()->flush();
	}

	protected function createFolders()
	{
		$folders = array(
			RENZO_ROOT . '/cache',
			RENZO_ROOT . '/sources',
			RENZO_ROOT . '/sources/Compiled',
			RENZO_ROOT . '/sources/Proxies',
			RENZO_ROOT . '/sources/GeneratedNodeSources',
		);

		foreach ($folders as $folder) {
			mkdir($folder, 0755, true);
		}
	}

	protected function installBackofficeTheme()
	{
		$existing = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findOneBy(array('backendTheme'=>true, 'available'=>true));

		if ($existing === null) {
			$beTheme = new Theme();
			$beTheme->setClassName('\Themes\Rozier\RozierApp');
			$beTheme->setAvailable(true);
			$beTheme->setBackendTheme(true);

			Kernel::getInstance()->em()->persist($beTheme);
		}
	}

	protected function installDefaultTranslation()
	{
		$existing = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Translation')
			->findOneBy(array('defaultTranslation'=>true, 'available'=>true));

		if ($existing === null) {
			$translation = new Translation();
			$translation->setLocale('en_GB');
			$translation->setDefaultTranslation(true);
			$translation->setName(Translation::$availableLocales[$translation->getLocale()]);
			$translation->setAvailable(true);

			Kernel::getInstance()->em()->persist($translation);
		}
	}

	public function createDefaultUser( $data )
	{
		$existing = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\User')
			->findOneBy(array('username'=>$data['username'], 'email'=>$data['email']));

		if ($existing === null) {
			$user = new User();
			$user->setUsername($data['username']);
			$user->setPlainPassword($data['password']);
			$user->setEmail($data['email']);

			$user->addRole($this->getRole(Role::ROLE_BACKEND_USER));
			$user->addRole($this->getRole(Role::ROLE_SUPER_ADMIN));

			Kernel::getInstance()->em()->persist($user);

			Kernel::getInstance()->em()->flush();
		}
		return true;
	}

	/**
     * Get role by name, and create it if does not exist
     * @param  string $roleName
     * @return Role
     */
    protected function getRole( $roleName = Role::ROLE_SUPER_ADMIN )
    {
        $role = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Role')
                ->findOneBy(array('name'=>$roleName));

        if ($role === null) {
            $role = new Role($roleName);
            Kernel::getInstance()->em()->persist($role);
            Kernel::getInstance()->em()->flush();
        }

        return $role;
    }
    /**
     * Get role by name, and create it if does not exist
     * @param  string $roleName
     * @return Role
     */
    protected function getSetting( $name )
    {
        $setting = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Setting')
                ->findOneBy(array('name'=>$name));

        if ($setting === null) {
            $setting = new Setting();
            $setting->setName($name);
            Kernel::getInstance()->em()->persist($setting);
            Kernel::getInstance()->em()->flush();
        }

        return $setting;
    }

    public function saveInformations( $data )
    {
    	/*
    	 * Save settings
    	 */
    	$set1 = $this->getSetting('site_name');
    	$set1->setValue($data['site_name']);
    	$set1->setType(NodeTypeField::STRING_T);
        Kernel::getInstance()->em()->flush();

        $set2 = $this->getSetting('email_sender');
    	$set2->setValue($data['email_sender']);
    	$set2->setType(NodeTypeField::EMAIL_T);
        Kernel::getInstance()->em()->flush();

        $set2 = $this->getSetting('meta_description');
    	$set2->setValue($data['meta_description']);
    	$set2->setType(NodeTypeField::TEXT_T);
        Kernel::getInstance()->em()->flush();

        /*
         * Install default theme
         */
        $this->installFrontendTheme();
    }

    protected function installFrontendTheme()
	{
		$existing = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Theme')
			->findOneBy(array(
				'backendTheme'=>false, 
				'available'=>true
			));

		if ($existing === null) {
			$feTheme = new Theme();
			$feTheme->setClassName('\Themes\DefaultTheme\Controllers\DefaultApp');
			$feTheme->setAvailable(true);
			$feTheme->setBackendTheme(false);

			Kernel::getInstance()->em()->persist($feTheme);
			Kernel::getInstance()->em()->flush();
		}
	}
}