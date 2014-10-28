<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file CacheCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Command line utils for managing Cache from terminal.
 */
class CacheCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this->setName('cache')
            ->setDescription('Manage cache and compiled data.')
            ->addOption(
                'clear-doctrine',
                null,
                InputOption::VALUE_NONE,
                'Clear doctrine metadata cache and entities proxies.'
            )
            ->addOption(
                'clear-routes',
                null,
                InputOption::VALUE_NONE,
                'Clear compiled route collections.'
            )
            ->addOption(
                'clear-assets',
                null,
                InputOption::VALUE_NONE,
                'Clear compiled route collections'
            )
            ->addOption(
                'clear-templates',
                null,
                InputOption::VALUE_NONE,
                'Clear compiled Twig templates.'
            )
            ->addOption(
                'clear-all',
                null,
                InputOption::VALUE_NONE,
                'Clear all caches (Doctrine, proxies, routes, templates, assets)'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";

        if ($input->getOption('clear-all')) {
            $text .= static::clearDoctrine();
            $text .= static::clearRouteCollections();
            $text .= static::clearCachedAssets();
            $text .= static::clearTemplates();

            $text .= '<info>All caches have been been purged…</info>'.PHP_EOL;
        } else {

            if ($input->getOption('clear-doctrine')) {
                $text .= static::clearDoctrine();
            }

            if ($input->getOption('clear-routes')) {
                $text .= static::clearRouteCollections();
            }

            if ($input->getOption('clear-assets')) {
                $text .= static::clearCachedAssets();
            }

            if ($input->getOption('clear-templates')) {
                $text .= static::clearTemplates();
            }
        }

        $output->writeln($text);
    }


    /**
     * Clear doctrine caches and rebuild entities proxies.
     *
     * @return string
     */
    public static function clearDoctrine()
    {
        $text = '';
        // Empty result cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
            $text .= 'Result cache: '.$cacheDriver->getNamespace().' — ';
            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
            $text .= PHP_EOL;
        }

        // Empty hydratation cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getHydrationCacheImpl();
        if ($cacheDriver !== null) {
            $text .= 'Hydratation cache: '.$cacheDriver->getNamespace().' — ';
            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
            $text .= PHP_EOL;
        }

        // Empty query cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getQueryCacheImpl();
        if ($cacheDriver !== null) {
            $text .= 'Query cache: '.$cacheDriver->getNamespace().' — ';
            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
            $text .= PHP_EOL;
        }

        // Empty metadata cache
        $cacheDriver = Kernel::getService('em')->getConfiguration()->getMetadataCacheImpl();
        if ($cacheDriver !== null) {
            $text .= 'Metadata cache: '.$cacheDriver->getNamespace().' — ';
            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
            $text .= PHP_EOL;
        }

        /*
         * Recreate proxies files
         */
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->files()->in(RENZO_ROOT . '/gen-src/Proxies');
        $fs->remove($finder);

        $meta = Kernel::getService('em')->getMetadataFactory()->getAllMetadata();
        $proxyFactory = Kernel::getService('em')->getProxyFactory();
        $proxyFactory->generateProxyClasses($meta, RENZO_ROOT . '/gen-src/Proxies');
        $text .= '<info>Doctrine proxy classes has been purged…</info>'.PHP_EOL;

        return $text;
    }

    /**
     * Clear compiled route collections.
     *
     * @return string
     */
    public static function clearRouteCollections()
    {
        $text = '';

        $fs = new Filesystem();
        $finder = new Finder();
        $finder->files()->in(RENZO_ROOT . '/gen-src/Compiled');
        $fs->remove($finder);

        $text .= '<info>Compiled route collections have been purged…</info>'.PHP_EOL;

        return $text;
    }

    /**
     * Clear compiled route collections.
     *
     * @return string
     */
    public static function clearCachedAssets()
    {
        $text = '';

        $fs = new Filesystem();
        $finder = new Finder();

        if (file_exists(RENZO_ROOT . '/cache/request') &&
            file_exists(RENZO_ROOT . '/cache/rendered')) {

            $finder->in(RENZO_ROOT . '/cache/request')
                   ->in(RENZO_ROOT . '/cache/rendered');
            $fs->remove($finder);

            $text .= '<info>Assets cache has been purged…</info>'.PHP_EOL;
        }

        return $text;
    }

    /**
     * Clear compiled route collections.
     *
     * @return string
     */
    public static function clearTemplates()
    {
        $text = '';

        $fs = new Filesystem();
        $finder = new Finder();
        $finder->in(RENZO_ROOT . '/cache/twig_cache');
        $fs->remove($finder);

        $text .= '<info>Compiled Twig templates have been purged…</info>'.PHP_EOL;

        return $text;
    }
}