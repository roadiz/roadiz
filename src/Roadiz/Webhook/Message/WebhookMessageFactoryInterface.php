<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Message;

use RZ\Roadiz\Message\HttpRequestMessage;
use RZ\Roadiz\Webhook\Entity\WebhookInterface;

interface WebhookMessageFactoryInterface
{
    public function createMessage(WebhookInterface $webhook): HttpRequestMessage;
}
