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
 * @file ExceptionSubscriber.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Exceptions\MaintenanceModeException;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Core\Viewers\ExceptionViewer;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Twig\Error\RuntimeError;

/**
 * Class ExceptionSubscriber
 * @package RZ\Roadiz\Core\Events
 */
class ExceptionSubscriber implements EventSubscriberInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var ExceptionViewer
     */
    protected $viewer;
    /**
     * @var ThemeResolverInterface
     */
    private $themeResolver;

    /**
     * ExceptionSubscriber constructor.
     *
     * @param Container              $container
     * @param ThemeResolverInterface $themeResolver
     * @param LoggerInterface        $logger
     * @param bool                   $debug
     */
    public function __construct(Container $container, ThemeResolverInterface $themeResolver, LoggerInterface $logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;

        $this->viewer = new ExceptionViewer();
        $this->themeResolver = $themeResolver;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        /*
         * Roadiz exception handling must be triggered AFTER firewall exceptions
         */
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -1],
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        /*
         * Get previous exception if thrown in Twig execution context.
         */
        if ($exception instanceof RuntimeError &&
            null !== $exception->getPrevious()) {
            $exception = $exception->getPrevious();
        }

        if (!$this->viewer->isFormatJson($event->getRequest())) {
            /*
             * Themed exception pages…
             */
            if ($exception instanceof MaintenanceModeException &&
                null !== $ctrl = $exception->getController()) {
                $response = $ctrl->maintenanceAction($event->getRequest());
                // Set http code according to status
                $response->setStatusCode($this->viewer->getHttpStatusCode($exception));
                $event->setResponse($response);
                return;
            } elseif (null !== $theme = $this->isNotFoundExceptionWithTheme($event)) {
                $event->setResponse($this->createThemeNotFoundResponse($theme, $exception, $event));
                return;
            }
        }

        // Customize your response object to display the exception details
        $response = $this->getEmergencyResponse($exception, $event->getRequest());
        // Set http code according to status
        $response->setStatusCode($this->viewer->getHttpStatusCode($exception));

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->headers->replace($exception->getHeaders());
        }

        if ($response instanceof JsonResponse) {
            $response->headers->set('Content-Type', 'application/problem+json');
        }
        // Send the modified response object to the event
        $event->setResponse($response);
    }

    /**
     * Create an emergency response to be sent instead of error logs.
     *
     * @param \Exception $e
     * @param Request $request
     *
     * @return Response
     */
    protected function getEmergencyResponse(\Exception $e, Request $request)
    {
        /*
         * Log error before displaying a fallback page.
         */
        $class = get_class($e);

        $this->logger->emergency($e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'exception' => $class,
        ]);

        return $this->viewer->getResponse($e, $request, $this->debug);
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @return null|Theme
     */
    protected function isNotFoundExceptionWithTheme(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $request = $event->getRequest();

        if ($exception instanceof ResourceNotFoundException ||
            $exception instanceof NotFoundHttpException ||
            (null !== $exception->getPrevious() && $exception->getPrevious() instanceof ResourceNotFoundException)
        ) {
            if (null !== $theme = $this->themeResolver->findTheme($request->getHost())) {
                /*
                 * 404 page
                 */
                if ($request instanceof \RZ\Roadiz\Core\HttpFoundation\Request) {
                    $request->setTheme($theme);
                }

                return $theme;
            }
        }

        return null;
    }

    /**
     * @param Theme                        $theme
     * @param \Exception                   $exception
     * @param GetResponseForExceptionEvent $event
     *
     * @return Response
     */
    protected function createThemeNotFoundResponse(Theme $theme, \Exception $exception, GetResponseForExceptionEvent $event)
    {
        /*
         * Create a new controller for serving
         * 404 response
         */
        /** @var string $ctrl */
        $ctrlClass = $theme->getClassName();
        $controller = new $ctrlClass();

        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->getContainer());
        }
        if ($controller instanceof AppController) {
            $controller->__init();
        }

        if ($exception instanceof NoTranslationAvailableException) {
            $event->getRequest()->setLocale($this->get('defaultTranslation')->getLocale());
        }

        return call_user_func_array([$controller, 'throw404'], [
            'message' => $exception->getMessage()
        ]);
    }
}
