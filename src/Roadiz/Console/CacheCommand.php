<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Events\Cache\CachePurgeAssetsRequestEvent;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\AppCacheClearer;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\NodesSourcesUrlsCacheClearer;
use RZ\Roadiz\Utils\Clearer\RoutingCacheClearer;
use RZ\Roadiz\Utils\Clearer\TemplatesCacheClearer;
use RZ\Roadiz\Utils\Clearer\TranslationsCacheClearer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Command line utils for managing Cache from terminal.
 */
class CacheCommand extends Command
{
    private $nsCacheHelper;

    protected function configure()
    {
        $this->setName('cache:clear')
            ->setDescription('Manage cache and compiled data.')
            ->addOption(
                'clear-configuration',
                'g',
                InputOption::VALUE_NONE,
                'Clear compiled configuration.'
            )
            ->addOption(
                'clear-doctrine',
                'd',
                InputOption::VALUE_NONE,
                'Clear doctrine metadata cache and entities proxies.'
            )
            ->addOption(
                'clear-routes',
                'r',
                InputOption::VALUE_NONE,
                'Clear compiled route collections.'
            )
            ->addOption(
                'clear-assets',
                null,
                InputOption::VALUE_NONE,
                'Clear all cached image thumbnails.'
            )
            ->addOption(
                'clear-templates',
                't',
                InputOption::VALUE_NONE,
                'Clear compiled Twig templates.'
            )
            ->addOption(
                'clear-translations',
                'l',
                InputOption::VALUE_NONE,
                'Clear compiled translations catalogues.'
            )
            ->addOption(
                'clear-nsurls',
                'u',
                InputOption::VALUE_NONE,
                'Clear cached node-sources Urls.'
            )
            ->addOption(
                'clear-appcache',
                'a',
                InputOption::VALUE_NONE,
                'Clear application cache.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            /** @var Kernel $kernel */
            $kernel = $this->getHelper('kernel')->getKernel();
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $kernel->get('dispatcher');
            /** @var ManagerRegistry $managerRegistry */
            $managerRegistry = $kernel->get(ManagerRegistry::class);
            $this->nsCacheHelper = $this->getHelper('ns-cache');

            $doctrineClearer = new DoctrineCacheClearer($managerRegistry, $kernel);
            $routingClearer = new RoutingCacheClearer($kernel->getCacheDir());
            $templatesClearer = new TemplatesCacheClearer($kernel->getCacheDir());
            $translationsClearer = new TranslationsCacheClearer($kernel->getCacheDir());
            $configurationClearer = new ConfigurationCacheClearer($kernel->getCacheDir());
            $appCacheClearer = new AppCacheClearer($kernel->getCacheDir());
            $nodeSourcesUrlsClearer = new NodesSourcesUrlsCacheClearer($this->nsCacheHelper->getCacheProvider());

            $io->note('Clearing cache for ' . $kernel->getEnvironment() . ($kernel->isPreview() ? ' [Preview]' : '') . ' environment.');

            $outputs = [];

            if ($input->getOption('clear-configuration')) {
                $configurationClearer->clear();
                $outputs[] = $configurationClearer->getOutput();
            } elseif ($input->getOption('clear-appcache')) {
                $appCacheClearer->clear();
                $outputs[] = $appCacheClearer->getOutput();
            } elseif ($input->getOption('clear-doctrine')) {
                $doctrineClearer->clear();
                $outputs[] = $doctrineClearer->getOutput();
            } elseif ($input->getOption('clear-routes')) {
                $routingClearer->clear();
                $outputs[] = $routingClearer->getOutput();
            } elseif ($input->getOption('clear-assets')) {
                $event = new CachePurgeAssetsRequestEvent($kernel);
                $dispatcher->dispatch($event);
                foreach ($event->getMessages() as $message) {
                    $outputs[] = sprintf('<info>%s</info>: %s', $message['description'], $message['message']);
                }
                foreach ($event->getErrors() as $message) {
                    $outputs[] = sprintf('<info>%s</info>: <error>%s</error>', $message['description'], $message['message']);
                }
            } elseif ($input->getOption('clear-templates')) {
                $templatesClearer->clear();
                $outputs[] = $templatesClearer->getOutput();
            } elseif ($input->getOption('clear-translations')) {
                $translationsClearer->clear();
                $outputs[] = $translationsClearer->getOutput();
            } elseif ($input->getOption('clear-nsurls')) {
                $nodeSourcesUrlsClearer->clear();
                $outputs[] = $nodeSourcesUrlsClearer->getOutput();
            } else {
                $event = new CachePurgeRequestEvent($kernel);
                $dispatcher->dispatch($event);

                foreach ($event->getMessages() as $message) {
                    $outputs[] = sprintf('<info>%s</info>: %s', $message['description'], $message['message']);
                }
                foreach ($event->getErrors() as $message) {
                    $outputs[] = sprintf('<info>%s</info>: <error>%s</error>', $message['description'], $message['message']);
                }
            }
            $io->listing($outputs);
            $io->success('Caches have been been purged.');
        } catch (\PDOException $e) {
            $io->warning('Can’t connect to database to empty Doctrine caches.');
        } catch (ConnectionException $e) {
            $io->warning('Can’t connect to database to empty Doctrine caches.');
        }
        return 0;
    }
}
