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
 * @file LoginAttemptManager.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication\Manager;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\LoginAttempt;
use RZ\Roadiz\Core\Exceptions\TooManyLoginAttemptsException;
use RZ\Roadiz\Core\Repositories\LoginAttemptRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class LoginAttemptManager
{
    /**
     * @var RequestStack
     */
    protected $requestStack;
    /**
     * @var int
     */
    protected $ipAttemptGraceTime = 20 * 60;
    /**
     * @var int
     */
    protected $ipAttemptCount = 20;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var LoginAttemptRepository
     */
    private $loginAttemptRepository;

    /**
     * LoginAttemptManager constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $username
     */
    public function checkLoginAttempts(string $username): void
    {
        /*
         * Checks if there are more than 10 failed attempts
         * from same IP address in the last 20 minutes
         */
        if ($this->getLoginAttemptRepository()->isIpAddressBlocked(
            $this->requestStack->getMasterRequest()->getClientIp(),
            $this->getIpAttemptGraceTime(),
            $this->getIpAttemptCount()
        )) {
            throw new TooManyRequestsHttpException(
                $this->getIpAttemptGraceTime(),
                'Too many login attemps for current IP address, wait before trying again.'
            );
        }
        if ($this->getLoginAttemptRepository()->isUsernameBlocked($username)) {
            throw new TooManyLoginAttemptsException(
                'Too many login attemps for this username, wait before trying again.',
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function onFailedLoginAttempt(string $username)
    {
        $loginAttempt = $this->getLoginAttemptRepository()->findOrCreateOneByIpAddressAndUsername(
            $this->requestStack->getMasterRequest()->getClientIp(),
            $username
        );

        $loginAttempt->addAttemptCount();
        $blocksUntil = new \DateTime();

        if ($loginAttempt->getAttemptCount() >= 9) {
            $blocksUntil->add(new \DateInterval('PT30M'));
            $loginAttempt->setBlocksLoginUntil($blocksUntil);
        } elseif ($loginAttempt->getAttemptCount() >= 6) {
            $blocksUntil->add(new \DateInterval('PT15M'));
            $loginAttempt->setBlocksLoginUntil($blocksUntil);
        } elseif ($loginAttempt->getAttemptCount() >= 3) {
            $blocksUntil->add(new \DateInterval('PT3M'));
            $loginAttempt->setBlocksLoginUntil($blocksUntil);
        }
        $this->entityManager->flush();
        return $this;
    }

    /**
     * @return LoginAttemptRepository
     */
    public function getLoginAttemptRepository(): LoginAttemptRepository
    {
        if (null === $this->loginAttemptRepository) {
            $this->loginAttemptRepository = $this->entityManager->getRepository(LoginAttempt::class);
        }
        return $this->loginAttemptRepository;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function onSuccessLoginAttempt(string $username)
    {
        $this->getLoginAttemptRepository()->resetLoginAttempts(
            $this->requestStack->getMasterRequest()->getClientIp(),
            $username
        );
        return $this;
    }

    /**
     * @return int
     */
    public function getIpAttemptGraceTime(): int
    {
        return $this->ipAttemptGraceTime;
    }

    /**
     * @return int
     */
    public function getIpAttemptCount(): int
    {
        return $this->ipAttemptCount;
    }
}
