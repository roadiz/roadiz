<?php
declare(strict_types=1);

namespace RZ\Roadiz\Preview\EventSubscriber;

use RZ\Roadiz\Core\KernelInterface;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PreviewBarSubscriber implements EventSubscriberInterface
{
    /**
     * @var PreviewResolverInterface
     */
    protected $previewResolver;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(PreviewResolverInterface $previewResolver)
    {
        $this->previewResolver = $previewResolver;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -128]
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
        if ($this->previewResolver->isPreview() &&
            $event->isMasterRequest() &&
            $response->getStatusCode() === Response::HTTP_OK &&
            false !== strpos($response->headers->get('Content-Type'), 'text/html')) {
            return true;
        }

        return false;
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if ($this->supports($event)) {
            $response = $event->getResponse();

            if (false !== strpos($response->getContent(), '</body>') &&
                false !== strpos($response->getContent(), '</head>')) {
                $content = str_replace(
                    '</head>',
                    "<style>#roadiz-preview-bar { font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", Helvetica, Arial, sans-serif; position: fixed; display: inline-flex; align-items: center; font-size: 9px; padding: 6px 10px 5px; bottom: 0; left: 1em; background-color: #ffe200; color: #923f00; border-radius: 3px 3px 0 0; text-transform: uppercase; letter-spacing: 0.005em; z-index: 9999;} #roadiz-preview-bar svg { width: 14px; margin-right: 5px;}</style></head>",
                    $response->getContent()
                );
                $content = str_replace(
                    '</body>',
                    "<div id=\"roadiz-preview-bar\"><svg aria-hidden=\"true\" data-prefix=\"fas\" data-icon=\"exclamation-triangle\" class=\"svg-inline--fa fa-exclamation-triangle fa-w-18\" role=\"img\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 576 512\"><path fill=\"currentColor\" d=\"M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z\"></path></svg>Preview</div></body>",
                    $content
                );
                $response->setContent($content);
                $event->setResponse($response);
            }
        }
    }
}
