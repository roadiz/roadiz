<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use RZ\Roadiz\Webhook\Entity\Webhook;

interface WebhookMessage
{
    /**
     * @param Webhook $webhook
     * @return static
     */
    public static function fromWebhook(Webhook $webhook);
}
