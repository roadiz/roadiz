<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Pimple\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

final class DebugBarSubscriber implements EventSubscriberInterface
{
    /**
     * @var null|Container
     */
    protected $container = null;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::CONTROLLER => 'onControllerMatched',
        ];
    }

    /**
     * @param ResponseEvent $event
     *
     * @return bool
     */
    protected function supports(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type');
        if (
            $this->container['settingsBag']->get('display_debug_panel') == true &&
            is_string($contentType) &&
            false !== strpos($contentType, 'text/html')
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->supports($event)) {
            /** @var Stopwatch $stopWatch */
            $stopWatch = $this->container['stopwatch'];
            $response = $event->getResponse();

            if ($stopWatch->isStarted('controllerHandling')) {
                $stopWatch->stop('controllerHandling');
            }
            if ($stopWatch->isStarted('twigRender')) {
                $stopWatch->stop('twigRender');
            }

            if ($stopWatch->isStarted('__section__')) {
                $stopWatch->stopSection('runtime');
            }

            if (false !== strpos($response->getContent(), '</body>') &&
                false !== strpos($response->getContent(), '</head>')) {
                $content = str_replace(
                    '</head>',
                    $this->container['debugbar.renderer']->renderHead() . "</head>",
                    $response->getContent()
                );
                $content = str_replace(
                    '</body>',
                    $this->container['debugbar.renderer']->render() . "</body>",
                    $content
                );
                $response->setContent($content);
                $event->setResponse($response);
            }
        }
    }

    /**
     * Start a stopwatch event when a kernel start handling.
     */
    public function onKernelRequest()
    {
        $this->container['stopwatch']->start('requestHandling');
        $this->container['stopwatch']->start('matchingRoute');
    }
    /**
     * Stop request-handling stopwatch event and
     * start a new stopwatch event when a controller is instantiated.
     */
    public function onControllerMatched()
    {
        $this->container['stopwatch']->stop('matchingRoute');
        $this->container['stopwatch']->stop('requestHandling');
        $this->container['stopwatch']->start('controllerHandling');
    }
}
