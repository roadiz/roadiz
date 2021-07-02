<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;

/**
 * @package RZ\Roadiz\Webhook\Entity
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="webhooks", indexes={
 *  @ORM\Index(name="webhook_message_type", columns={"message_type"}),
 *  @ORM\Index(name="webhook_created_at", columns={"created_at"}),
 *  @ORM\Index(name="webhook_updated_at", columns={"updated_at"}),
 *  @ORM\Index(name="webhook_automatic", columns={"automatic"}),
 *  @ORM\Index(name="webhook_last_triggered_at", columns={"last_triggered_at"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Webhook extends AbstractDateTimed
{
    /**
     * @var string|null
     * @ORM\Id
     * @ORM\Column(type="string", length=36)
     * @ORM\GeneratedValue(strategy="UUID")
     * @Serializer\Groups("id")
     * @Serializer\Type("string")
     */
    protected $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true, name="message_type")
     * @Serializer\Type("string")
     */
    protected ?string $messageType = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Type("string")
     */
    protected ?string $uri = null;

    /**
     * @var array|null
     * @ORM\Column(type="json", nullable=true)
     * @Serializer\Type("array")
     */
    protected ?array $payload = null;

    /**
     * @var int Wait between webhook call and webhook triggering request.
     * @ORM\Column(type="integer", nullable=false)
     * @Serializer\Type("int")
     */
    protected int $throttleSeconds = 60;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true, name="last_triggered_at")
     * @Serializer\Type("\DateTime")
     */
    protected ?\DateTime $lastTriggeredAt = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, name="automatic", options={"default" = false})
     * @Serializer\Type("boolean")
     */
    protected bool $automatic = false;

    /**
     * @return string|null
     */
    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    /**
     * @param string|null $messageType
     * @return Webhook
     */
    public function setMessageType(?string $messageType): Webhook
    {
        $this->messageType = $messageType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @param string|null $uri
     * @return Webhook
     */
    public function setUri(?string $uri): Webhook
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * @param array|null $payload
     * @return Webhook
     */
    public function setPayload(?array $payload): Webhook
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @return int
     */
    public function getThrottleSeconds(): int
    {
        return $this->throttleSeconds;
    }

    /**
     * @return \DateInterval
     * @throws \Exception
     */
    public function getThrottleInterval(): \DateInterval
    {
        return new \DateInterval('PT'.$this->getThrottleSeconds().'S');
    }

    /**
     * @return \DateTime|null
     * @throws \Exception
     */
    public function doNotTriggerBefore(): ?\DateTime
    {
        if (null === $this->getLastTriggeredAt()) {
            return null;
        }
        $doNotTriggerBefore = clone $this->getLastTriggeredAt();
        return $doNotTriggerBefore->add($this->getThrottleInterval());
    }

    /**
     * @param int $throttleSeconds
     * @return Webhook
     */
    public function setThrottleSeconds(int $throttleSeconds): Webhook
    {
        $this->throttleSeconds = $throttleSeconds;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastTriggeredAt(): ?\DateTime
    {
        return $this->lastTriggeredAt;
    }

    /**
     * @param \DateTime|null $lastTriggeredAt
     * @return Webhook
     */
    public function setLastTriggeredAt(?\DateTime $lastTriggeredAt): Webhook
    {
        $this->lastTriggeredAt = $lastTriggeredAt;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutomatic(): bool
    {
        return $this->automatic;
    }

    /**
     * @param bool $automatic
     * @return Webhook
     */
    public function setAutomatic(bool $automatic): Webhook
    {
        $this->automatic = $automatic;
        return $this;
    }

    public function __toString()
    {
        return $this->getId() ?? substr($this->getUri() ?? '', 0, 30);
    }
}
