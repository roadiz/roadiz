<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Entity;

interface WebhookInterface
{
    /**
     * @return string|null
     */
    public function getId();
    /**
     * @return string
     */
    public function __toString();
    public function getUri(): ?string;
    public function getMessageType(): ?string;
    public function getPayload(): ?array;
    public function getThrottleSeconds(): int;
    public function doNotTriggerBefore(): ?\DateTime;
    public function setLastTriggeredAt(?\DateTime $lastTriggeredAt);
    public function getLastTriggeredAt(): ?\DateTime;
    public function isAutomatic(): bool;
}
