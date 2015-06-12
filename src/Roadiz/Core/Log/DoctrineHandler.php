<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file DoctrineHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Log;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * A log system which store message in database.
 */
class DoctrineHandler extends AbstractProcessingHandler
{
    protected $em = null;
    protected $tokenStorage = null;
    protected $user = null;
    protected $request = null;

    public function __construct(
        EntityManager $em,
        TokenStorageInterface $tokenStorage,
        Request $request,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->request = $request;

        parent::__construct($level, $bubble);
    }

    /**
     * @return TokenStorageInterface
     */
    public function getTokenStorage()
    {
        return $this->tokenStorage;
    }
    /**
     * @param TokenStorageInterface $tokenStorage
     *
     * @return $this
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;

        return $this;
    }


    /**
     * @return RZ\Roadiz\Core\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param array  $record
     */
    public function write(array $record)
    {
        if ($this->em->isOpen()) {
            $log = new Log(
                $record['level'],
                $record['message']
            );

            /*
             * Use available securityAuthorizationChecker to provide a valid user
             */
            if (null !== $this->getTokenStorage() &&
                null !== $this->getTokenStorage()->getToken() &&
                is_object($this->getTokenStorage()->getToken()->getUser()) &&
                null !== $this->getTokenStorage()->getToken()->getUser()->getId()) {
                $log->setUser($this->getTokenStorage()->getToken()->getUser());
            }
            /*
             * Use manually set user
             */
            if (null !== $this->getUser()) {
                $log->setUser($this->getUser());
            }

            /*
             * Add client IP to log if it’s an HTTP request
             */
            if (null !== $this->getRequest()) {
                $log->setClientIp($this->getRequest()->getClientIp());
            }

            /*
             * Add a related node-source entity
             */
            if (isset($record['context']['source']) &&
                null !== $record['context']['source'] &&
                $record['context']['source'] instanceof NodesSources) {
                $log->setNodeSource($record['context']['source']);
            }

            $this->em->persist($log);
            $this->em->flush();
        }
    }
}
