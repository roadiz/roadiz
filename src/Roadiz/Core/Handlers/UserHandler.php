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
 * @file UserHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

/**
 * Handle operations with users entities.
 */
class UserHandler extends AbstractHandler
{
    private $user;

    /** @var EncoderFactory  */
    private $encoderFactory;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Create a new handler with user to handle.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
        $this->encoderFactory = Kernel::getService('userEncoderFactory');
    }

    /**
     * Encode current User password.
     * @return $this
     * @throws \Exception
     */
    public function encodePassword()
    {
        if ($this->user->getPlainPassword() != '') {
            $encoder = $this->encoderFactory->getEncoder($this->user);
            $encodedPassword = $encoder->encodePassword(
                $this->user->getPlainPassword(),
                $this->user->getSalt()
            );
            $this->user->setPassword($encodedPassword);
        } else {
            throw new \Exception("User plain password is empty", 1);
        }

        return $this;
    }

    /**
     * @param string $plainPassword Submitted password to validate
     *
     * @return boolean
     * @deprecated Use directly encoderFactory service.
     */
    public function isPasswordValid($plainPassword)
    {
        $encoder = $this->encoderFactory->getEncoder($this->user);

        return $encoder->isPasswordValid(
            $this->user->getPassword(),
            $plainPassword,
            $this->user->getSalt()
        );
    }
}
