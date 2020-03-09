<?php
/**
 * Copyright (c) 2020. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file LoginAttempt.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
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
     * @ORM\Column(type="string", length=50, nullable=true, name="ip_address")
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $date;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true, name="blocks_login_until")
     */
    private $blocksLoginUntil;

    /**
     * @ORM\Column(type="string", nullable=false, name="username", unique=false)
     */
    private $username;

    /**
     * @ORM\Column(type="integer", nullable=true, name="attempt_count")
     */
    private $attemptCount;

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
