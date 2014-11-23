<?php
/*
 * Copyright © 2014, REZO ZERO
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Fixtures.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Install\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;

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

        // Clear result cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $cacheDriver->deleteAll();
        }
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
            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
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
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
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
            ->getRepository('RZ\Roadiz\Core\Entities\User')
            ->findOneBy(array('username'=>$data['username'], 'email'=>$data['email']));

        if ($existing === null) {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setPlainPassword($data['password']);
            $user->setEmail($data['email']);

            $url = "http://www.gravatar.com/avatar/".
                    md5(strtolower(trim($user->getEmail()))).
                    "?d=identicon&s=200";

            $user->setPictureUrl($url);

            $existingGroup = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Group')
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
                ->getRepository('RZ\Roadiz\Core\Entities\Role')
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
     * @return RZ\Roadiz\Core\Entities\Role
     */
    protected function getSetting($name)
    {
        $setting = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Setting')
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
    }

    public function installTheme($data)
    {
        /*
         * Install default theme
         */
        $this->installFrontendTheme($data['className']);
    }

    /**
     * @return void
     */
    protected function installFrontendTheme($classname)
    {
        $existing = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
            ->findOneByClassName($classname);

        if (null === $existing) {
            $feTheme = new Theme();
            $feTheme->setClassName($classname);
            $feTheme->setAvailable(true);
            $feTheme->setBackendTheme(false);

            Kernel::getService('em')->persist($feTheme);
            Kernel::getService('em')->flush();

            // Clear result cache
            $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null) {
                $cacheDriver->deleteAll();
            }
        }
    }
}
