<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPathGeneratingEvent;
use RZ\Roadiz\Core\Routing\NodesSourcesPathAggregator;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeSourcePathSubscriber implements EventSubscriberInterface
{
    /**
     * @var NodesSourcesPathAggregator
     */
    protected $pathAggregator;

    /**
     * @param NodesSourcesPathAggregator $pathAggregator
     */
    public function __construct(NodesSourcesPathAggregator $pathAggregator)
    {
        $this->pathAggregator = $pathAggregator;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            NodesSourcesPathGeneratingEvent::class => [['onNodesSourcesPath', -100]],
        ];
    }

    /**
     * @param NodesSourcesPathGeneratingEvent $event
     */
    public function onNodesSourcesPath(NodesSourcesPathGeneratingEvent $event): void
    {
        $urlGenerator = new NodesSourcesUrlGenerator(
            $this->pathAggregator,
            null,
            $event->getNodeSource(),
            $event->isForceLocale(),
            $event->isForceLocaleWithUrlAlias()
        );
        $event->setPath($urlGenerator->getNonContextualUrl($event->getTheme(), $event->getParameters()));
    }
}
