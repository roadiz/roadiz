<?php
declare(strict_types=1);

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
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;

/**
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
     * @param Container              $container
     * @param ThemeResolverInterface $themeResolver
     * @param LoggerInterface        $logger
     * @param bool                   $debug
     */
    public function __construct(
        Container $container,
        ThemeResolverInterface $themeResolver,
        LoggerInterface $logger,
        $debug = false
    ) {
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
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();

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
                try {
                    $response = $ctrl->maintenanceAction($event->getRequest());
                    // Set http code according to status
                    $response->setStatusCode($this->viewer->getHttpStatusCode($exception));
                    $event->setResponse($response);
                    return;
                } catch (LoaderError $error) {
                    // Twig template does not exist
                }
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
        /*
         * Do not flood logs with not-found errors
         */
        if (!($e instanceof NotFoundHttpException) && !($e instanceof ResourceNotFoundException)) {
            if ($e instanceof HttpExceptionInterface) {
                // If HTTP exception do not log to critical
                $this->logger->notice($e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'exception' => $class,
                ]);
            } else {
                $this->logger->emergency($e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'exception' => $class,
                ]);
            }
        }

        return $this->viewer->getResponse($e, $request, $this->debug);
    }

    /**
     * @param ExceptionEvent $event
     * @return null|Theme
     */
    protected function isNotFoundExceptionWithTheme(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
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
     * @param Theme          $theme
     * @param \Exception     $exception
     * @param ExceptionEvent $event
     *
     * @return Response
     */
    protected function createThemeNotFoundResponse(Theme $theme, \Exception $exception, ExceptionEvent $event)
    {
        /*
         * Create a new controller for serving
         * 404 response
         */
        $ctrlClass = $theme->getClassName();
        $controller = new $ctrlClass();

        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->getContainer());
        }
        if ($controller instanceof AppController) {
            $controller->__init();
        }

        if ($exception instanceof NoTranslationAvailableException ||
            $exception->getPrevious() instanceof NoTranslationAvailableException) {
            $event->getRequest()->setLocale($this->get('defaultTranslation')->getLocale());
        }

        return call_user_func_array([$controller, 'throw404'], [
            'message' => $exception->getMessage()
        ]);
    }
}
