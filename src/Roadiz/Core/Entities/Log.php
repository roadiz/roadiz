<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Log.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * Log Entity
 *
 * @Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @Table(name="log")
 * @HasLifecycleCallbacks
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
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function __construct($level, $message, array $context = array())
    {
        $this->level = $level;
        $this->message = $message;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\User")
     * @JoinColumn(name="user_id", referencedColumnName="id", unique=false, onDelete="CASCADE")
     */
    protected $user = null;

    /**
     * @return RZ\Roadiz\Core\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     *
     * @return RZ\Roadiz\Core\Entities\User
     */
    public function setUser(\RZ\Roadiz\Core\Entities\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Column(type="text", name="message")
     */
    protected $message = '';

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @Column(type="integer", name="level", nullable=false)
     */
    protected $level = null;

    /**
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @Column(type="datetime", name="datetime", nullable=false)
     */
    protected $datetime = null;

    /**
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @Column(type="string", name="client_ip", unique=false, nullable=true)
     */
    protected $clientIp = null;

    /**
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * @param string $clientIp
     *
     * @return string $clientIP
     */
    public function setClientIp($clientIp)
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    /**
     * @PrePersist
     */
    public function prePersist()
    {
        $this->datetime = new \DateTime("now");
    }
}
