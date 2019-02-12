<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file DatabaseController.php
 * @author Ambroise Maupate
 */
namespace Themes\Install\Controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use RZ\Roadiz\Config\YamlConfigurationHandler;
use RZ\Roadiz\Console\Tools\Fixtures;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Doctrine\SchemaUpdater;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Themes\Install\Forms\DatabaseType;
use Themes\Install\InstallApp;

/**
 * DatabaseController
 */
class DatabaseController extends InstallApp
{
    /**
     * Test database connexion with given configuration.
     *
     * @param array $connexion Doctrine array parameters
     *
     * @return bool
     * @throws \PDOException
     */
    protected function testDoctrineConnexion($connexion = [])
    {
        $config = Setup::createAnnotationMetadataConfiguration(
            [],
            true,
            null,
            null,
            false
        );

        $em = EntityManager::create($connexion, $config);
        return $em->getConnection()->connect();
    }
    /**
     * Install database screen.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function databaseAction(Request $request)
    {
        $databaseForm = $this->createForm(DatabaseType::class, $this->get('config')['doctrine']);
        /** @var YamlConfigurationHandler $yamlConfigHandler */
        $yamlConfigHandler = $this->get('config.handler');
        $databaseForm->handleRequest($request);

        if ($databaseForm->isValid()) {
            try {
                if (false !== $this->testDoctrineConnexion($databaseForm->getData())) {
                    $tempConf = $yamlConfigHandler->getConfiguration();
                    foreach ($databaseForm->getData() as $key => $value) {
                        $tempConf['doctrine'][$key] = $value;
                    }
                    $yamlConfigHandler->setConfiguration($tempConf);

                    /*
                     * Test connexion
                     */
                    /** @var Kernel $kernel */
                    $kernel = $this->get('kernel');
                    $fixtures = new Fixtures(
                        $this->get('em'),
                        $kernel->getCacheDir(),
                        $kernel->getRootDir() . '/conf/config.yml',
                        $kernel->getRootDir(),
                        $kernel->isDebug(),
                        $request
                    );
                    $fixtures->createFolders();
                    $yamlConfigHandler->writeConfiguration();

                    /*
                     * Need to clear configuration cache.
                     */
                    $configurationClearer = new ConfigurationCacheClearer($this->get('kernel')->getCacheDir());
                    $configurationClearer->clear();

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    return $this->redirect($this->generateUrl(
                        'installDatabaseSchemaPage'
                    ));
                }
                $databaseForm->addError(new FormError('Can\'t connect to database.'));
            } catch (\PDOException $e) {
                if (strstr($e->getMessage(), 'SQLSTATE[')) {
                    preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
                    $message = $matches[3];
                } else {
                    $message = $e->getMessage();
                }
                $databaseForm->addError(new FormError(ucfirst($message)));
            } catch (\Exception $e) {
                $databaseForm->addError(new FormError($e->getMessage()));
            }
        }
        $this->assignation['databaseForm'] = $databaseForm->createView();


        return $this->render('steps/database.html.twig', $this->assignation);
    }

    /**
     * Perform database schema migration.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function databaseSchemaAction(Request $request)
    {
        /*
         * Test connexion
         */
        if (null === $this->get('em')) {
            $this->assignation['error'] = true;
            $this->assignation['errorMessage'] = $this->get('session')->getFlashBag()->all();
        } else {
            try {
                /*
                 * Very important !
                 * Use updateSchema instead of create to enable upgrading
                 * Roadiz database using Install theme.
                 */
                $updater = new SchemaUpdater($this->get('em'), $this->get('kernel'));
                $updater->updateSchema();

                /*
                 * Force redirect to install fixtures
                 */
                return $this->redirect($this->generateUrl(
                    'installDatabaseFixturesPage'
                ));
            } catch (\PDOException $e) {
                if (strstr($e->getMessage(), 'SQLSTATE[')) {
                    preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
                    $message = $matches[3];
                } else {
                    $message = $e->getMessage();
                }
                $this->assignation['error'] = true;
                $this->assignation['errorMessage'] = ucfirst($message);
            } catch (\Exception $e) {
                $this->assignation['error'] = true;
                $this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
            }
        }

        return $this->render('steps/databaseError.html.twig', $this->assignation);
    }

    /**
     * Perform database fixtures importation.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function databaseFixturesAction(Request $request)
    {
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');

        $fixtures = new Fixtures(
            $this->get('em'),
            $kernel->getCacheDir(),
            $kernel->getRootDir() . '/conf/config.yml',
            $kernel->getRootDir(),
            $kernel->isDebug(),
            $request
        );
        $fixtures->installFixtures();

        $this->assignation['imports'] = [];
        /*
         * files to import
         */
        if (file_exists(InstallApp::getThemeFolder() . "/config.yml")) {
            $installData = Yaml::parse(file_get_contents(InstallApp::getThemeFolder() . "/config.yml"));
            $this->assignation['imports'] = $installData['importFiles'];
        }

        return $this->render('steps/databaseFixtures.html.twig', $this->assignation);
    }

    /**
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateSchemaAction(Request $request)
    {
        $updater = new SchemaUpdater($this->get('em'), $this->get('kernel'));
        $updater->updateSchema();

        return new JsonResponse(['status' => true]);
    }
    /**
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearDoctrineCacheAction(Request $request)
    {
        $doctrineClearer = new DoctrineCacheClearer($this->get('em'), $this->get('kernel'));
        $doctrineClearer->clear();

        return new JsonResponse(['status' => true]);
    }
}
