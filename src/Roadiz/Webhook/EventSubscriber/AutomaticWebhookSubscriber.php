<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Events\DocumentTranslationUpdatedEvent;
use RZ\Roadiz\Core\Events\DocumentUpdatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeDeletedEvent;
use RZ\Roadiz\Core\Events\Node\NodeTaggedEvent;
use RZ\Roadiz\Core\Events\Node\NodeVisibilityChangedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesDeletedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesPreUpdatedEvent;
use RZ\Roadiz\Core\Events\Tag\TagUpdatedEvent;
use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Exception\TooManyWebhookTriggeredException;
use RZ\Roadiz\Webhook\WebhookDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AutomaticWebhookSubscriber implements EventSubscriberInterface
{
    private WebhookDispatcher $webhookDispatcher;
    private EntityManagerInterface $entityManager;

    /**
     * @param WebhookDispatcher $webhookDispatcher
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(WebhookDispatcher $webhookDispatcher, EntityManagerInterface $entityManager)
    {
        $this->webhookDispatcher = $webhookDispatcher;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.node.completed' => ['onAutomaticWebhook'],
            NodeVisibilityChangedEvent::class => 'onAutomaticWebhook',
            NodesSourcesPreUpdatedEvent::class => 'onAutomaticWebhook',
            NodesSourcesDeletedEvent::class => 'onAutomaticWebhook',
            NodeDeletedEvent::class => 'onAutomaticWebhook',
            NodeTaggedEvent::class => 'onAutomaticWebhook',
            TagUpdatedEvent::class => 'onAutomaticWebhook',
            DocumentTranslationUpdatedEvent::class => 'onAutomaticWebhook',
            DocumentUpdatedEvent::class => 'onAutomaticWebhook',
        ];
    }

    public function onAutomaticWebhook(): void
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->entityManager->getRepository(Webhook::class)->findBy([
            'automatic' => true
        ]);
        foreach ($webhooks as $webhook) {
            try {
                $this->webhookDispatcher->dispatch($webhook);
            } catch (TooManyWebhookTriggeredException $e) {
                // do nothing
            }
        }
    }
}
