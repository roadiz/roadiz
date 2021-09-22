<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\SchemaException;
use RZ\Roadiz\Console\ThemeAwareCommandInterface;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Event dispatched to set up theme configuration at kernel request.
 */
class ThemesSubscriber implements EventSubscriberInterface
{
    private Kernel $kernel;
    private Stopwatch $stopwatch;

    /**
     * @param Kernel $kernel
     * @param Stopwatch $stopwatch
     */
    public function __construct(Kernel $kernel, Stopwatch $stopwatch)
    {
        $this->kernel = $kernel;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        /*
         * Theme request is between Locale and Firewall+routing
         */
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            KernelEvents::REQUEST => ['onKernelRequest', 60],
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommand(ConsoleCommandEvent $event)
    {
        /*
         * Call setupDependencyInjection method on each registered theme when command
         * is implementing ThemeAwareCommandInterface.
         *
         * Warning: This may lead to Doctrine exception if your database is not synced.
         */
        if ($event->getCommand() instanceof ThemeAwareCommandInterface) {
            try {
                /** @var ThemeResolverInterface $themeResolver */
                $themeResolver = $this->kernel->get('themeResolver');

                if (class_exists($themeResolver->getBackendClassName())) {
                    call_user_func([$themeResolver->getBackendClassName(), 'setupDependencyInjection'], $this->kernel->getContainer());
                }
                foreach ($themeResolver->getFrontendThemes() as $theme) {
                    $feClass = $theme->getClassName();
                    call_user_func([$feClass, 'setupDependencyInjection'], $this->kernel->getContainer());
                }
            } catch (ConnectionException $connectionException) {
                $event->getOutput()->writeln('<error>Database is not reachable.</error> Themes won’t be initialized!');
                $event->getOutput()->writeln('<error>'.$connectionException->getMessage().'</error>');
            } catch (SchemaException $schemaException) {
                $event->getOutput()->writeln('<error>Database synced is not synced.</error> Themes won’t be initialized!');
                $event->getOutput()->writeln('<error>'.$schemaException->getMessage().'</error>');
            } catch (TableNotFoundException $tableNotFoundException) {
                $event->getOutput()->writeln('<error>Database synced is not synced.</error> Themes won’t be initialized!');
                $event->getOutput()->writeln('<error>'.$tableNotFoundException->getMessage().'</error>');
            }
        }
    }

    /**
     * After a controller has been matched. We need to inject current
     * Kernel instance and main DI container.
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest()) {
            /** @var ThemeResolverInterface $themeResolver */
            $themeResolver = $this->kernel->get('themeResolver');
            /*
             * Register Themes dependency injection
             */
            if (!$this->kernel->isInstallMode() && class_exists($themeResolver->getBackendClassName())) {
                $this->stopwatch->start('backendDependencyInjection');
                // Register back-end security scheme
                call_user_func([$themeResolver->getBackendClassName(), 'setupDependencyInjection'], $this->kernel->getContainer());
                $this->stopwatch->stop('backendDependencyInjection');
            }

            $this->stopwatch->start('themeDependencyInjection');
            // Register front-end security scheme
            /** @var Theme $theme */
            foreach ($themeResolver->getFrontendThemes() as $theme) {
                call_user_func([$theme->getClassName(), 'setupDependencyInjection'], $this->kernel->getContainer());
            }
            $this->stopwatch->stop('themeDependencyInjection');
        }
    }
}
