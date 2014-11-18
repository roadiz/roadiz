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
                null !== $this->getSecurityContext()->getToken()->getUser()->getId()) {

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
