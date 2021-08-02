<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use RZ\Roadiz\Message\HttpRequestMessage;
use RZ\Roadiz\Webhook\Entity\WebhookInterface;

final class WebhookMessageFactory implements WebhookMessageFactoryInterface
{
    public function createMessage(WebhookInterface $webhook): HttpRequestMessage
    {
        if (null === $webhook->getMessageType()) {
            throw new \LogicException('Webhook message type is null.');
        }

        /** @var class-string $messageType */
        $messageType = $webhook->getMessageType();

        if (!class_exists($messageType)) {
            throw new \LogicException('Webhook message type does not exist.');
        }
        if (!in_array(WebhookMessage::class, class_implements($messageType))) {
            throw new \LogicException('Webhook message type does not implement ' . WebhookMessage::class);
        }

        return $messageType::fromWebhook($webhook);
    }
}
