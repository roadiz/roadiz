<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file Fixtures.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console\Tools;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Config\YamlConfigurationHandler;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * Fixtures class
 */
class Fixtures
{
    protected $entityManager;
    protected $request;
    protected $cacheDir;
    protected $debug;
    protected $configPath;
    private $rootDir;

    /**
     * @param EntityManager $entityManager
     * @param string $cacheDir
     * @param string $configPath
     * @param $rootDir
     * @param boolean $debug
     * @param Request|null $request
     */
    public function __construct(
        EntityManager $entityManager,
        $cacheDir,
        $configPath,
        $rootDir,
        $debug = true,
        Request $request = null
    ) {
        $this->entityManager = $entityManager;
        $this->request = $request;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
        $this->configPath = $configPath;
        $this->rootDir = $rootDir;
    }

    /**
     * @return void
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function installFixtures()
    {
        $this->installDefaultTranslation();
        $this->installBackofficeTheme();
        $this->entityManager->flush();
        $this->clearResultCache();
    }

    protected function clearResultCache()
    {
        $cacheDriver = $this->entityManager->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null && $cacheDriver instanceof CacheProvider) {
            $cacheDriver->deleteAll();
        }
    }

    /**
     * @return void
     */
    public function createFolders()
    {
        $fs = new Filesystem();

        $folders = [
            $this->rootDir . '/cache',
            $this->rootDir . '/gen-src/Compiled',
            $this->rootDir . '/gen-src/Proxies',
            $this->rootDir . '/gen-src/GeneratedNodeSources',
        ];

        foreach ($folders as $folder) {
            if (!$fs->exists($folder)) {
                $fs->mkdir($folder, 0755);
            }
        }
    }

    /**
     * @return void
     */
    protected function installBackofficeTheme()
    {
        $existing = $this->entityManager
                         ->getRepository(Theme::class)
                         ->findOneBy(['backendTheme' => true, 'available' => true]);

        if (null === $existing) {
            $beTheme = new Theme();
            $beTheme->setClassName(RozierApp::class);
            $beTheme->setAvailable(true);
            $beTheme->setBackendTheme(true);

            $this->entityManager->persist($beTheme);
        }
    }

    /**
     * @return void
     */
    protected function installDefaultTranslation()
    {
        $existing = $this->entityManager
                         ->getRepository(Translation::class)
                         ->findOneBy(['defaultTranslation' => true, 'available' => true]);

        if (null === $existing) {
            $translation = new Translation();

            /*
             * Create a translation according to
             * current language
             */
            if (null !== $this->request) {
                $translation->setLocale($this->request->getLocale());
            } else {
                $translation->setLocale('en');
            }

            $translation->setDefaultTranslation(true);
            $translation->setName(Translation::$availableLocales[$translation->getLocale()]);
            $translation->setAvailable(true);

            $this->entityManager->persist($translation);
        }
    }

    /**
     * @param array $data
     * @return boolean
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createDefaultUser($data)
    {
        $existing = $this->entityManager
                         ->getRepository(User::class)
                         ->findOneBy(['username' => $data['username'], 'email' => $data['email']]);

        if ($existing === null) {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setPlainPassword($data['password']);
            $user->setEmail($data['email']);
            $user->setPictureUrl($user->getGravatarUrl());

            $existingGroup = $this->entityManager
                                  ->getRepository(Group::class)
                                  ->findOneByName('Admin');
            $user->addGroup($existingGroup);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return true;
    }

    /**
     * Get role by name, and create it if does not exist.
     * @param string $roleName
     *
     * @return Role
     */
    protected function getRole($roleName = Role::ROLE_SUPERADMIN)
    {
        $role = $this->entityManager
                     ->getRepository(Role::class)
                     ->findOneBy(['name' => $roleName]);

        if ($role === null) {
            $role = new Role($roleName);
            $this->entityManager->persist($role);
            $this->entityManager->flush();
        }

        return $role;
    }
    /**
     * Get role by name, and create it if does not exist.
     * @param string $name
     *
     * @return \RZ\Roadiz\Core\Entities\Setting
     */
    protected function getSetting($name)
    {
        $setting = $this->entityManager
                        ->getRepository(Setting::class)
                        ->findOneBy(['name' => $name]);

        if (null === $setting) {
            $setting = new Setting();
            $setting->setName($name);
            $this->entityManager->persist($setting);
            $this->entityManager->flush();
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

        $set2 = $this->getSetting('seo_description');
        $set2->setValue($data['seo_description']);
        $set2->setType(NodeTypeField::TEXT_T);

        $set2 = $this->getSetting('display_debug_panel');
        $set2->setValue(false);
        $set2->setType(NodeTypeField::BOOLEAN_T);

        $this->entityManager->flush();

        /*
         * Update timezone
         */
        if (!empty($data['timezone'])) {
            $conf = new YamlConfigurationHandler(
                $this->cacheDir,
                $this->debug,
                $this->configPath
            );

            $config = $conf->load();
            $config['timezone'] = $data['timezone'];

            $conf->setConfiguration($config);
            $conf->writeConfiguration();
        }
    }

    /**
     * @param array $data
     * @deprecated  Frontend themes no more need to be registered in database.
     */
    public function installTheme($data)
    {
        /*
         * Install default theme
         */
        $this->installFrontendTheme($data['className']);
    }

    /**
     * Install theme and return its ID.
     *
     * @param $classname
     *
     * @return int
     * @deprecated Frontend themes no more need to be registered in database.
     */
    public function installFrontendTheme($classname)
    {
        /** @var Theme|null $existing */
        $existing = $this->entityManager
                         ->getRepository(Theme::class)
                         ->findOneByClassName($classname);

        if (null === $existing) {
            $feTheme = new Theme();
            $feTheme->setClassName($classname);
            $feTheme->setAvailable(true);
            $feTheme->setBackendTheme(false);

            $this->entityManager->persist($feTheme);
            $this->entityManager->flush();

            $this->clearResultCache();

            return $feTheme->getId();
        }

        return $existing->getId();
    }
}
