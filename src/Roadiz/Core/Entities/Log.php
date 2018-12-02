<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file Log.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * Log Entity
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\LogRepository")
 * @ORM\Table(name="log", indexes={
 *     @ORM\Index(columns={"datetime"}),
 *     @ORM\Index(columns={"level"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Log extends AbstractEntity
{
    const EMERGENCY = 0;
    const CRITICAL =  1;
    const ALERT =     2;
    const ERROR =     3;
    const WARNING =   4;
    const NOTICE =    5;
    const INFO =      6;
    const DEBUG =     7;
    const LOG =       8;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=false, onDelete="SET NULL")
     * @var User|null
     */
    protected $user = null;
    /**
     * @ORM\Column(type="text", name="message")
     * @var string
     */
    protected $message = '';
    /**
     * @ORM\Column(type="integer", name="level", nullable=false)
     * @var int
     */
    protected $level;
    /**
     * @ORM\Column(type="datetime", name="datetime", nullable=false)
     * @var \DateTime
     */
    protected $datetime;
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\NodesSources", inversedBy="logs")
     * @ORM\JoinColumn(name="node_source_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $nodeSource = null;
    /**
     * @ORM\Column(type="string", name="client_ip", unique=false, nullable=true)
     * @var string
     */
    protected $clientIp = null;

    /**
     * @param mixed  $level
     * @param string $message
     */
    public function __construct(int $level, string $message)
    {
        $this->level = $level;
        $this->message = $message;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Log
     */
    public function setUser(User $user): Log
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

    /**
     * Get log related node-source.
     *
     * @return NodesSources|null
     */
    public function getNodeSource(): ?NodesSources
    {
        return $this->nodeSource;
    }

    /**
     * @param NodesSources $nodeSource
     * @return $this
     */
    public function setNodeSource(NodesSources $nodeSource): Log
    {
        $this->nodeSource = $nodeSource;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    /**
     * @param string $clientIp
     * @return Log
     */
    public function setClientIp(string $clientIp): Log
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->datetime = new \DateTime("now");
    }
}
