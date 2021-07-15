<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook;

use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Exception\TooManyWebhookTriggeredException;
use RZ\Roadiz\Webhook\Message\WebhookMessageFactoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

final class ThrottledWebhookDispatcher implements WebhookDispatcher
{
    private WebhookMessageFactoryInterface $messageFactory;
    private MessageBusInterface $messageBus;
    private CacheStorage $cacheStorage;

    /**
     * @param WebhookMessageFactoryInterface $messageFactory
     * @param MessageBusInterface $messageBus
     * @param CacheStorage $cacheStorage
     */
    public function __construct(
        WebhookMessageFactoryInterface $messageFactory,
        MessageBusInterface $messageBus,
        CacheStorage $cacheStorage
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageBus = $messageBus;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @param Webhook $webhook
     * @throws \Exception
     */
    public function dispatch(Webhook $webhook): void
    {
        $doNotTriggerBefore = $webhook->doNotTriggerBefore();
        if (null !== $doNotTriggerBefore &&
            $doNotTriggerBefore > new \DateTime()) {
            throw new TooManyWebhookTriggeredException();
        }
        $rateLimiterFactory = new RateLimiterFactory([
            'id' => 'webhook_' . $webhook,
            'policy' => 'token_bucket',
            'limit' => 1,
            'rate' => ['interval' => $webhook->getThrottleSeconds() . ' seconds'],
        ], $this->cacheStorage);
        $limiter = $rateLimiterFactory->create($webhook->getId());
        $limit = $limiter->consume();
        // the argument of consume() is the number of tokens to consume
        // and returns an object of type Limit
        if (!$limit->isAccepted()) {
            throw new TooManyWebhookTriggeredException();
        }
        $message = $this->messageFactory->createMessage($webhook);
        $this->messageBus->dispatch(new Envelope($message));
        $webhook->setLastTriggeredAt(new \DateTime());
    }
}
