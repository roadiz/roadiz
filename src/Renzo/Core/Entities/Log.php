<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file Log.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;

/**
 * Log Entity
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
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
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\User")
     * @JoinColumn(name="user_id", referencedColumnName="id", unique=false)
     */
    protected $user = null;

    /**
     * @return RZ\Renzo\Core\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param RZ\Renzo\Core\Entities\User $user
     *
     * @return RZ\Renzo\Core\Entities\User
     */
    public function setUser(\RZ\Renzo\Core\Entities\User $user)
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