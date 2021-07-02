<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook;

use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Exception\TooManyWebhookTriggeredException;
use RZ\Roadiz\Webhook\Message\WebhookMessageFactoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ThrottledWebhookDispatcher implements WebhookDispatcher
{
    private WebhookMessageFactoryInterface $messageFactory;
    private MessageBusInterface $messageBus;
    private RateLimiterFactory $rateLimiterFactory;

    /**
     * @param WebhookMessageFactoryInterface $messageFactory
     * @param MessageBusInterface $messageBus
     * @param RateLimiterFactory $rateLimiterFactory
     */
    public function __construct(
        WebhookMessageFactoryInterface $messageFactory,
        MessageBusInterface $messageBus,
        RateLimiterFactory $rateLimiterFactory
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageBus = $messageBus;
        $this->rateLimiterFactory = $rateLimiterFactory;
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

        $limiter = $this->rateLimiterFactory->create($webhook->getId());
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
