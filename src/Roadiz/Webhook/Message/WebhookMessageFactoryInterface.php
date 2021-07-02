<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use RZ\Roadiz\Message\HttpRequestMessage;
use RZ\Roadiz\Webhook\Entity\Webhook;

interface WebhookMessageFactoryInterface
{
    public function createMessage(Webhook $webhook): HttpRequestMessage;
}
