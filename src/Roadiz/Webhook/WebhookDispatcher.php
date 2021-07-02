<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook;

use RZ\Roadiz\Webhook\Entity\Webhook;

interface WebhookDispatcher
{
    public function dispatch(Webhook $webhook): void;
}
