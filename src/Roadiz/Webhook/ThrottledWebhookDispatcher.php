<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Message\WebhookMessageFactoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final class ThrottledWebhookDispatcher implements WebhookDispatcher
{
    private WebhookMessageFactoryInterface $messageFactory;
    private MessageBusInterface $messageBus;

    /**
     * @param WebhookMessageFactoryInterface $messageFactory
     * @param MessageBusInterface $messageBus
     */
    public function __construct(
        WebhookMessageFactoryInterface $messageFactory,
        MessageBusInterface $messageBus,
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageBus = $messageBus;
    }

    /**
     * @param Webhook $webhook
     */
    public function dispatch(Webhook $webhook): void
    {
        $message = $this->messageFactory->createMessage($webhook);
        $this->messageBus->dispatch(new Envelope($message, [
            new DelayStamp($webhook->getThrottleSeconds() * 1000)
        ]));
        $webhook->setLastTriggeredAt(new \DateTime());
    }
}
