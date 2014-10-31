<?php

namespace RZ\Renzo\Core\Services;

use Pimple\Container;

use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

use RZ\Renzo\Core\Events\DataInheritanceEvent;
use RZ\Renzo\Core\Kernel;

/**
 * Register Doctrine services for dependency injection container.
 */
class DoctrineServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Initialize Doctrine entity manager in DI container.
     *
     * This method can be called from InstallApp after updating
     * doctrine configuration.
     *
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        if ($container['config'] !== null &&
            isset($container['config']["doctrine"])) {

            $container['em'] = function ($c) {
                try {
                    $c['stopwatch']->start('initDoctrine');
                    // the connection configuration
                    $configDB = Setup::createAnnotationMetadataConfiguration(
                        $c['entitiesPaths'],
                        (boolean) $c['config']['devMode']
                    );

                    $configDB->setProxyDir(RENZO_ROOT . '/gen-src/Proxies');
                    $configDB->setProxyNamespace('Proxies');

                    $em = EntityManager::create($c['config']["doctrine"], $configDB);

                    $evm = $em->getEventManager();

                    /*
                     * Create dynamic dicriminator map for our Node system
                     */
                    $evm->addEventListener(Events::loadClassMetadata, new DataInheritanceEvent());

                    if ($em->getConfiguration()->getResultCacheImpl() !== null) {
                        $em->getConfiguration()
                                ->getResultCacheImpl()
                                ->setNamespace($c['config']["appNamespace"]);
                    }
                    if ($em->getConfiguration()->getHydrationCacheImpl() !== null) {
                        $em->getConfiguration()
                                ->getHydrationCacheImpl()
                                ->setNamespace($c['config']["appNamespace"]);
                    }
                    if ($em->getConfiguration()->getQueryCacheImpl() !== null) {
                        $em->getConfiguration()
                                ->getQueryCacheImpl()
                                ->setNamespace($c['config']["appNamespace"]);
                    }
                    if ($em->getConfiguration()->getMetadataCacheImpl()) {
                        $em->getConfiguration()
                                ->getMetadataCacheImpl()
                                ->setNamespace($c['config']["appNamespace"]);
                    }

                    $c['stopwatch']->stop('initDoctrine');

                    return $em;
                } catch (\PDOException $e) {
                    $c['session']->getFlashBag()->add('error', $e->getMessage());
                    return null;
                } catch (\Exception $e) {
                    $c['session']->getFlashBag()->add('error', $e->getMessage());
                    return null;
                }
            };
        }
    }
}
