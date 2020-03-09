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
 * @file AttemptAwareDaoAuthenticationProvider.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication\Provider;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Authentication\LoginAttemptAwareInterface;
use RZ\Roadiz\Core\Authentication\Manager\LoginAttemptManager;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AttemptAwareDaoAuthenticationProvider extends DaoAuthenticationProvider implements LoginAttemptAwareInterface
{
    /**
     * @var LoginAttemptManager
     */
    protected $loginAttemptManager;

    /**
     * AttemptAwareDaoAuthenticationProvider constructor.
     *
     * @param EntityManagerInterface  $entityManager
     * @param UserProviderInterface   $userProvider
     * @param UserCheckerInterface    $userChecker
     * @param string                  $providerKey
     * @param EncoderFactoryInterface $encoderFactory
     * @param bool                    $hideUserNotFoundExceptions
     */
    public function __construct(
        LoginAttemptManager $loginAttemptManager,
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        string $providerKey,
        EncoderFactoryInterface $encoderFactory,
        bool $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, $hideUserNotFoundExceptions);
        $this->loginAttemptManager = $loginAttemptManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $this->getLoginAttemptManager()->checkLoginAttempts($username);
        return parent::retrieveUser($username, $token);
    }

    /**
     * @return LoginAttemptManager
     */
    public function getLoginAttemptManager(): LoginAttemptManager
    {
        return $this->loginAttemptManager;
    }

    /**
     * @param LoginAttemptManager $loginAttemptManager
     *
     * @return AttemptAwareDaoAuthenticationProvider
     */
    public function setLoginAttemptManager(
        LoginAttemptManager $loginAttemptManager
    ): AttemptAwareDaoAuthenticationProvider {
        $this->loginAttemptManager = $loginAttemptManager;

        return $this;
    }
}
