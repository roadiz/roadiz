<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file Fixtures.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

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
* Fixtures class
*/
class Fixtures
{

    /**
     * @return void
     */
    public function installFixtures()
    {
        $this->installDefaultTranslation();
        $this->installBackofficeTheme();

        Kernel::getService('em')->flush();
    }

    /**
     * @return void
     */
    public function createFolders()
    {
        $folders = array(
            RENZO_ROOT . '/cache',
            RENZO_ROOT . '/sources/Compiled',
            RENZO_ROOT . '/sources/Proxies',
            RENZO_ROOT . '/sources/GeneratedNodeSources',
        );

        foreach ($folders as $folder) {
            if (!file_exists($folder)) {
                if (!mkdir($folder, 0755, true)) {
                    throw(new \Exception('Impossible to create “'.$folder.'” folder.'));
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function installBackofficeTheme()
    {
        $existing = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array('backendTheme'=>true, 'available'=>true));

        if (null === $existing) {
            $beTheme = new Theme();
            $beTheme->setClassName('\Themes\Rozier\RozierApp');
            $beTheme->setAvailable(true);
            $beTheme->setBackendTheme(true);

            Kernel::getService('em')->persist($beTheme);
        }
    }

    /**
     * @return void
     */
    protected function installDefaultTranslation()
    {
        $existing = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Translation')
            ->findOneBy(array('defaultTranslation'=>true, 'available'=>true));

        if (null === $existing) {
            $translation = new Translation();

            /*
             * Create a translation according to
             * current language
             */
            switch (Kernel::getInstance()->getRequest()->getLocale()) {
                case 'fr':
                    $translation->setLocale('fr');
                    break;
                default:
                    $translation->setLocale('en');
                    break;
            }

            $translation->setDefaultTranslation(true);
            $translation->setName(Translation::$availableLocales[$translation->getLocale()]);
            $translation->setAvailable(true);

            Kernel::getService('em')->persist($translation);
        }
    }

    /**
     * @param array $data
     *
     * @return boolean
     */
    public function createDefaultUser($data)
    {
        $existing = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\User')
            ->findOneBy(array('username'=>$data['username'], 'email'=>$data['email']));

        if ($existing === null) {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setPlainPassword($data['password']);
            $user->setEmail($data['email']);

            $existingGroup = Kernel::getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Group')
                ->findOneByName('Admin');
            $user->addGroup($existingGroup);

            Kernel::getService('em')->persist($user);
            Kernel::getService('em')->flush();
        }

        return true;
    }

    /**
     * Get role by name, and create it if does not exist.
     * @param string $roleName
     *
     * @return Role
     */
    protected function getRole($roleName = Role::ROLE_SUPER_ADMIN)
    {
        $role = Kernel::getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Role')
                ->findOneBy(array('name'=>$roleName));

        if ($role === null) {
            $role = new Role($roleName);
            Kernel::getService('em')->persist($role);
            Kernel::getService('em')->flush();
        }

        return $role;
    }
    /**
     * Get role by name, and create it if does not exist.
     * @param string $name
     *
     * @return RZ\Renzo\Core\Entities\Role
     */
    protected function getSetting($name)
    {
        $setting = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Setting')
            ->findOneBy(array('name'=>$name));

        if (null === $setting) {
            $setting = new Setting();
            $setting->setName($name);
            Kernel::getService('em')->persist($setting);
            Kernel::getService('em')->flush();
        }

        return $setting;
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function saveInformations($data)
    {
        /*
         * Save settings
         */
        $set1 = $this->getSetting('site_name');
        $set1->setValue($data['site_name']);
        $set1->setType(NodeTypeField::STRING_T);

        $set2 = $this->getSetting('email_sender');
        $set2->setValue($data['email_sender']);
        $set2->setType(NodeTypeField::EMAIL_T);

        $set2 = $this->getSetting('email_sender_name');
        $set2->setValue($data['email_sender_name']);
        $set2->setType(NodeTypeField::STRING_T);

        $set2 = $this->getSetting('meta_description');
        $set2->setValue($data['meta_description']);
        $set2->setType(NodeTypeField::TEXT_T);

        $set2 = $this->getSetting('display_debug_panel');
        $set2->setValue(false);
        $set2->setType(NodeTypeField::BOOLEAN_T);

        Kernel::getService('em')->flush();

        /*
         * Update timezone
         */
        if (!empty($data['timezone'])) {
            $conf = new Configuration();
            $config = $conf->getConfiguration();
            $config['timezone'] = $data['timezone'];

            $conf->setConfiguration($config);
            $conf->writeConfiguration();
        }

        /*
         * Install default theme
         */
        $this->installFrontendTheme();
    }

    /**
     * @return void
     */
    protected function installFrontendTheme()
    {
        $existing = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findOneBy(array(
                'backendTheme'=>false,
                'available'=>true
            ));

        if (null === $existing) {
            $feTheme = new Theme();
            $feTheme->setClassName('\Themes\DefaultTheme\DefaultApp');
            $feTheme->setAvailable(true);
            $feTheme->setBackendTheme(false);

            Kernel::getService('em')->persist($feTheme);
            Kernel::getService('em')->flush();
        }
    }
}
