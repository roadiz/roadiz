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
 * @file UserHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Log\Logger;
use Symfony\Component\Security\Core\Util\SecureRandom;

/**
 * Handle operations with users entities.
 */
class UserHandler
{
    private $user;

    /**
     * @return RZ\Roadiz\Core\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Create a new handler with user to handle.
     *
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Encode current User password.
     *
     * @return $this
     */
    public function encodePassword()
    {
        if ($this->user->getPlainPassword() != '') {
            $encoder = Kernel::getService('userEncoderFactory')->getEncoder($this->user);
            $encodedPassword = $encoder->encodePassword(
                $this->user->getPlainPassword(),
                $this->user->getSalt()
            );
            $this->user->setPassword($encodedPassword);
        } else {
            throw new Exception("User plain password is empty", 1);
        }

        return $this;
    }

    /**
     * @param string $plainPassword Submitted password to validate
     *
     * @return boolean
     */
    public function isPasswordValid($plainPassword)
    {
        $encoder = Kernel::getService('userEncoderFactory')->getEncoder($this->user);

        return $encoder->isPasswordValid(
            $this->user->getPassword(),
            $plainPassword,
            $this->user->getSalt()
        );
    }

    /**
     * @param integer $length Password length
     *
     * @return string New password
     */
    public static function generatePassword($length = 9)
    {
        $lowercase = "qwertyuiopasdfghjklzxcvbnm";
        $uppercase = "ASDFGHJKLZXCVBNMQWERTYUIOP";
        $numbers = "1234567890";
        $specialcharacters = "{}[]:.<>?_+!@#";
        $randomCode = "";
        mt_srand(crc32(microtime()));
        $max = strlen($lowercase) - 1;

        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $lowercase{mt_rand(0, $max)};
        }
        $max = strlen($uppercase) - 1;

        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $uppercase{mt_rand(0, $max)};
        }
        $max = strlen($specialcharacters) - 1;

        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $specialcharacters{mt_rand(0, $max)};
        }
        $max = strlen($numbers) - 1;

        for ($x = 0; $x < abs($length/3); $x++) {
            $randomCode .= $numbers{mt_rand(0, $max)};
        }

        return str_shuffle($randomCode);
    }
}
