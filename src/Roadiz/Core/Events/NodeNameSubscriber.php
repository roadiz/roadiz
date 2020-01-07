<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeNameSubscriber.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Monolog\Logger;
use RZ\Roadiz\Core\Events\Node\NodePathChangedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPreUpdatedEvent;
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
    /** @var Logger */
    private $logger;
    /** @var NodeNameChecker */
    private $nodeNameChecker;

    /**
     * NodeNameSubscriber constructor.
     *
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
                !$this->nodeNameChecker->isNodeNameWithUniqId($testingNodeName, $nodeSource->getNode()->getNodeName())) {
                if ($nodeSource->getNode()->getNodeType()->isReachable()) {
                    $oldPaths = $this->nodeMover->getNodeSourcesUrls($nodeSource->getNode());
                    $oldUpdateAt = $nodeSource->getNode()->getUpdatedAt();
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
