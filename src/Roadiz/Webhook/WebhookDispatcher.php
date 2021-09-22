<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook;

use RZ\Roadiz\Webhook\Entity\WebhookInterface;

interface WebhookDispatcher
{
    public function dispatch(WebhookInterface $webhook): void;
}
