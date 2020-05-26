<?php
declare(strict_types=1);

namespace Themes\Install\Controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use RZ\Roadiz\Config\YamlConfigurationHandler;
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
     * Test database connection with given configuration.
     *
     * @param array $connection Doctrine array parameters
     *
     * @return bool
     * @throws \PDOException|\Doctrine\ORM\ORMException
     */
    protected function testDoctrineConnection($connection = [])
    {
        $config = Setup::createAnnotationMetadataConfiguration(
            [],
            true,
            null,
            null,
            false
        );

        $em = EntityManager::create($connection, $config);
        return $em->getConnection()->connect();
    }

    /**
     * Install database screen.
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function databaseAction(Request $request)
    {
        $databaseForm = $this->createForm(DatabaseType::class, $this->get('config')['doctrine']);
        /** @var YamlConfigurationHandler $yamlConfigHandler */
        $yamlConfigHandler = $this->get('config.handler');
        $databaseForm->handleRequest($request);

        if ($databaseForm->isSubmitted() && $databaseForm->isValid()) {
            try {
                if (false !== $this->testDoctrineConnection($databaseForm->getData())) {
                    $tempConf = $yamlConfigHandler->getConfiguration();
                    foreach ($databaseForm->getData() as $key => $value) {
                        $tempConf['doctrine'][$key] = $value;
                    }
                    $yamlConfigHandler->setConfiguration($tempConf);

                    /*
                     * Test connection
                     */
                    $fixtures = $this->getFixtures($request);

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
     * @throws \Twig_Error_Runtime
     */
    public function databaseSchemaAction(Request $request)
    {
        /*
         * Test connection
         */
        if (null === $this->get('em')) {
            $this->assignation['error'] = true;
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
                $this->publishErrorMessage($request, ucfirst($message));
            } catch (\Exception $e) {
                $this->assignation['error'] = true;
                $this->publishErrorMessage($request, $e->getMessage() . PHP_EOL . $e->getTraceAsString());
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
     * @throws \ReflectionException
     * @throws \Twig_Error_Runtime
     */
    public function databaseFixturesAction(Request $request)
    {
        $fixtures = $this->getFixtures($request);
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
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateSchemaAction(Request $request)
    {
        $updater = new SchemaUpdater($this->get('em'), $this->get('kernel'));
        $updater->updateSchema();

        return new JsonResponse(['status' => true]);
    }
    /**
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
