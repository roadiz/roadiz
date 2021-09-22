<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use RZ\Roadiz\Webhook\Entity\WebhookInterface;

interface WebhookMessage
{
    /**
     * @param WebhookInterface $webhook
     * @return static
     */
    public static function fromWebhook(WebhookInterface $webhook);
}
