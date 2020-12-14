<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Monolog\Logger;
use RZ\Roadiz\Core\Events\Node\NodePathChangedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPreUpdatedEvent;
use RZ\Roadiz\Utils\Node\Exception\SameNodeUrlException;
use RZ\Roadiz\Utils\Node\NodeMover;
use RZ\Roadiz\Utils\Node\NodeNameChecker;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates node name against default node-source title is applicable.
 */
class NodeNameSubscriber implements EventSubscriberInterface
{
    /**
     * @var NodeMover
     */
    protected $nodeMover;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var NodeNameChecker
     */
    private $nodeNameChecker;

    /**
     * @param Logger          $logger
     * @param NodeNameChecker $nodeNameChecker
     * @param NodeMover       $nodeMover
     */
    public function __construct(Logger $logger, NodeNameChecker $nodeNameChecker, NodeMover $nodeMover)
    {
        $this->logger = $logger;
        $this->nodeNameChecker = $nodeNameChecker;
        $this->nodeMover = $nodeMover;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            NodesSourcesPreUpdatedEvent::class => ['onBeforeUpdate', 0],
        ];
    }

    /**
     * @param NodesSourcesPreUpdatedEvent  $event
     * @param string                       $eventName
     * @param EventDispatcherInterface     $dispatcher
     */
    public function onBeforeUpdate(NodesSourcesPreUpdatedEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $nodeSource = $event->getNodeSource();
        $title = $nodeSource->getTitle();

        /*
         * Update node name if dynamic option enabled and
         * default translation
         */
        if ("" != $title &&
            true === $nodeSource->getNode()->isDynamicNodeName() &&
            $nodeSource->getTranslation()->isDefaultTranslation()) {
            $testingNodeName = StringHandler::slugify($title);

            /*
             * Node name wont be updated if name already taken OR
             * if it is ALREADY suffixed with a unique ID.
             */
            if ($testingNodeName != $nodeSource->getNode()->getNodeName() &&
                $this->nodeNameChecker->isNodeNameValid($testingNodeName) &&
                !$this->nodeNameChecker->isNodeNameWithUniqId(
                    $testingNodeName,
                    $nodeSource->getNode()->getNodeName()
                )) {
                try {
                    if ($nodeSource->getNode()->getNodeType()->isReachable()) {
                        $oldPaths = $this->nodeMover->getNodeSourcesUrls($nodeSource->getNode());
                        $oldUpdateAt = $nodeSource->getNode()->getUpdatedAt();
                    }
                } catch (SameNodeUrlException $e) {
                    $oldPaths = [];
                }
                $alreadyUsed = $this->nodeNameChecker->isNodeNameAlreadyUsed($title);
                if (!$alreadyUsed) {
                    $nodeSource->getNode()->setNodeName($title);
                } else {
                    $nodeSource->getNode()->setNodeName($title . '-' . uniqid());
                }

                /*
                 * Dispatch event
                 */
                if (isset($oldPaths) && isset($oldUpdateAt) && count($oldPaths) > 0) {
                    $dispatcher->dispatch(new NodePathChangedEvent($nodeSource->getNode(), $oldPaths, $oldUpdateAt));
                }
                $dispatcher->dispatch(new NodeUpdatedEvent($nodeSource->getNode()));
            } else {
                $this->logger->debug('Node name has not be changed.');
            }
        }
    }
}
