<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Logger.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Log;

use RZ\Renzo\Core\Entities\Log;
use RZ\Renzo\Core\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * A log system which store message in database.
 */
class Logger implements LoggerInterface
{
    /**
     * @var Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext = null;
    /**
     * @return Symfony\Component\Security\Core\SecurityContextInterface
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }
    /**
     * @param Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     *
     * @return $this
     */
    public function setSecurityContext(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;

        return $this;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     */
    public function emergency($message, array $context = array())
    {
        $this->log(Log::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     */
    public function alert($message, array $context = array())
    {
        $this->log(Log::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     */
    public function critical($message, array $context = array())
    {
        $this->log(Log::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     */
    public function error($message, array $context = array())
    {
        $this->log(Log::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     */
    public function warning($message, array $context = array())
    {
        $this->log(Log::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     */
    public function notice($message, array $context = array())
    {
        $this->log(Log::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     */
    public function info($message, array $context = array())
    {
        $this->log(Log::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     */
    public function debug($message, array $context = array())
    {
        //$this->log(Log::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        if (Kernel::getService('em')->isOpen()) {

            $log = new Log($level, $message, $context);

            if (null !== $this->getSecurityContext() &&
                null !== $this->getSecurityContext()->getToken() &&
                null !== $this->getSecurityContext()->getToken()->getUser() &&
                is_object($this->getSecurityContext()->getToken()->getUser())) {

                $log->setUser($this->getSecurityContext()->getToken()->getUser());
            }

            /*
             * Add client IP to log if it’s an HTTP request
             */
            if (null !== Kernel::getInstance()->getRequest()) {
                $log->setClientIp(Kernel::getInstance()->getRequest()->getClientIp());
            }

            Kernel::getService('em')->persist($log);
            Kernel::getService('em')->flush();
        }
    }
}
