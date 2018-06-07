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
 * @file ThemesSubscriber.php
 * @author Ambroise Maupate
 */
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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Event dispatched to setup theme configuration at kernel request.
 */
class ThemesSubscriber implements EventSubscriberInterface
{
    private $kernel;
    private $stopwatch;

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

                call_user_func([$themeResolver->getBackendClassName(), 'setupDependencyInjection'], $this->kernel->getContainer());
                /** @var Theme $theme */
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
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            /** @var ThemeResolverInterface $themeResolver */
            $themeResolver = $this->kernel->get('themeResolver');
            /*
             * Register Themes dependency injection
             */
            if (!$this->kernel->isInstallMode()) {
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
