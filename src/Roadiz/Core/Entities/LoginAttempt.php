<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\LoginAttemptRepository")
 * @ORM\Table(name="login_attempts", indexes={
 *     @ORM\Index(columns={"username"}),
 *     @ORM\Index(columns={"blocks_login_until", "username"}),
 *     @ORM\Index(columns={"blocks_login_until", "username", "ip_address"})
 * })
 */
class LoginAttempt
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true, name="ip_address")
     */
    private $ipAddress = null;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $date;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true, name="blocks_login_until")
     */
    private $blocksLoginUntil = null;

    /**
     * @ORM\Column(type="string", nullable=false, name="username", unique=false)
     */
    private $username;

    /**
     * @ORM\Column(type="integer", nullable=true, name="attempt_count")
     */
    private $attemptCount = null;

    public function __construct(?string $ipAddress, ?string $username)
    {
        $this->ipAddress = $ipAddress;
        $this->username = $username;
        $this->date = new \DateTimeImmutable('now');
        $this->blocksLoginUntil = new \DateTime('now');
        $this->attemptCount = 0;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return \DateTime|null
     */
    public function getBlocksLoginUntil(): ?\DateTime
    {
        return $this->blocksLoginUntil;
    }

    /**
     * @param \DateTime $blocksLoginUntil
     *
     * @return LoginAttempt
     */
    public function setBlocksLoginUntil(\DateTime $blocksLoginUntil): LoginAttempt
    {
        $this->blocksLoginUntil = $blocksLoginUntil;

        return $this;
    }

    /**
     * @return int
     */
    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    /**
     * @return LoginAttempt
     */
    public function addAttemptCount(): LoginAttempt
    {
        $this->attemptCount++;
        return $this;
    }
}
