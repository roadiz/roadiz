<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Bags\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SignatureListener.
 *
 * @package RZ\Roadiz\Core\Events
 */
final class SignatureListener implements EventSubscriberInterface
{
    /**
     * @var Settings
     */
    protected $settingsBag;
    private $version;
    private $debug;

    /**
     * @param Settings $settingsBag
     * @param string   $version
     * @param bool     $debug
     */
    public function __construct(Settings $settingsBag, $version, $debug = false)
    {
        $this->version = $version;
        $this->debug = $debug;
        $this->settingsBag = $settingsBag;
    }
    /**
     * Filters the Response.
     *
     * @param ResponseEvent $event A ResponseEvent instance
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest() || $this->settingsBag->get('hide_roadiz_version', false)) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->add(['X-Powered-By' => 'Roadiz CMS']);

        if ($this->debug && $this->version) {
            $response->headers->add(['X-Version' => $this->version]);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
