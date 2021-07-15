<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\DocumentTranslationUpdatedEvent;
use RZ\Roadiz\Core\Events\DocumentUpdatedEvent;
use RZ\Roadiz\Core\Events\FilterNodeEvent;
use RZ\Roadiz\Core\Events\Node\NodeDeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeTaggedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeVisibilityChangedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesDeletedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPreUpdatedEvent;
use RZ\Roadiz\Core\Events\Tag\TagUpdatedEvent;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Exception\TooManyWebhookTriggeredException;
use RZ\Roadiz\Webhook\WebhookDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

final class AutomaticWebhookSubscriber implements EventSubscriberInterface
{
    private WebhookDispatcher $webhookDispatcher;
    private EntityManagerInterface $entityManager;
    private HandlerFactoryInterface $handlerFactory;

    /**
     * @param WebhookDispatcher $webhookDispatcher
     * @param EntityManagerInterface $entityManager
     * @param HandlerFactoryInterface $handlerFactory
     */
    public function __construct(WebhookDispatcher $webhookDispatcher, EntityManagerInterface $entityManager, HandlerFactoryInterface $handlerFactory)
    {
        $this->webhookDispatcher = $webhookDispatcher;
        $this->entityManager = $entityManager;
        $this->handlerFactory = $handlerFactory;
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.node.completed' => ['onAutomaticWebhook'],
            NodeVisibilityChangedEvent::class => 'onAutomaticWebhook',
            NodesSourcesPreUpdatedEvent::class => 'onAutomaticWebhook',
            NodesSourcesDeletedEvent::class => 'onAutomaticWebhook',
            NodeUpdatedEvent::class => 'onAutomaticWebhook',
            NodeDeletedEvent::class => 'onAutomaticWebhook',
            NodeTaggedEvent::class => 'onAutomaticWebhook',
            TagUpdatedEvent::class => 'onAutomaticWebhook',
            DocumentTranslationUpdatedEvent::class => 'onAutomaticWebhook',
            DocumentUpdatedEvent::class => 'onAutomaticWebhook',
        ];
    }

    /**
     * @param mixed $event
     * @return bool
     */
    protected function isEventRelatedToNode($event): bool
    {
        return $event instanceof Event ||
            $event instanceof NodeVisibilityChangedEvent ||
            $event instanceof NodesSourcesPreUpdatedEvent ||
            $event instanceof NodesSourcesDeletedEvent ||
            $event instanceof NodeUpdatedEvent ||
            $event instanceof NodeDeletedEvent ||
            $event instanceof NodeTaggedEvent;
    }

    /**
     * @param Event|NodeVisibilityChangedEvent|NodesSourcesPreUpdatedEvent|NodesSourcesDeletedEvent|NodeDeletedEvent|NodeTaggedEvent|TagUpdatedEvent|DocumentTranslationUpdatedEvent|DocumentUpdatedEvent $event
     */
    public function onAutomaticWebhook($event): void
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->entityManager->getRepository(Webhook::class)->findBy([
            'automatic' => true
        ]);
        foreach ($webhooks as $webhook) {
            if (!$this->isEventRelatedToNode($event) || $this->isEventSubjectInRootNode($event, $webhook->getRootNode())) {
                /*
                 * Always Triggers automatic webhook if there is no registered root node, or
                 * event is not related to a node.
                 */
                try {
                    $this->webhookDispatcher->dispatch($webhook);
                } catch (TooManyWebhookTriggeredException $e) {
                    // do nothing
                }
            }
        }
    }

    private function isEventSubjectInRootNode($event, ?Node $rootNode): bool
    {
        if (null === $rootNode) {
            /*
             * If root node does not exist, subject is always in root.
             */
            return true;
        }
        /** @var Node|null $subject */
        $subject = null;

        switch (true) {
            case $event instanceof Event:
                $subject = $event->getSubject();
                if (!$subject instanceof Node) {
                    return false;
                }
                break;
            case $event instanceof NodeUpdatedEvent:
            case $event instanceof NodeDeletedEvent:
            case $event instanceof NodeTaggedEvent:
            case $event instanceof NodeVisibilityChangedEvent:
                $subject = $event->getNode();
                break;
            case $event instanceof NodesSourcesPreUpdatedEvent:
            case $event instanceof NodesSourcesDeletedEvent:
                $subject = $event->getNodeSource()->getNode();
                break;
            default:
                return false;
        }

        $handler = $this->handlerFactory->getHandler($subject);
        if ($handler instanceof NodeHandler) {
            return $handler->isRelatedToNode($rootNode);
        }

        return false;
    }
}
