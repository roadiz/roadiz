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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\User;

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
            throw new \Exception("User plain password is empty", 1);
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
     * Generates a strong password of N length containing at least one lower case letter,
     * one uppercase letter, one digit, and one special character. The remaining characters
     * in the password are chosen at random from those four sets.
     *
     * The available characters in each set are user friendly - there are no ambiguous
     * characters such as i, l, 1, o, 0, etc. This, coupled with the $add_dashes option,
     * makes it much easier for users to manually type or speak their passwords.
     *
     * Note: the $add_dashes option will increase the length of the password by
     * floor(sqrt(N)) characters.
     *
     * @param  integer $length
     * @param  boolean $add_dashes
     * @param  string  $available_sets
     *
     * @return string
     *
     * @see https://gist.github.com/tylerhall/521810
     */
    public static function generatePassword($length = 9, $add_dashes = false, $available_sets = 'luds')
    {
        $sets = array();
        if (strpos($available_sets, 'l') !== false) {
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }
        if (strpos($available_sets, 'u') !== false) {
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }
        if (strpos($available_sets, 'd') !== false) {
            $sets[] = '23456789';
        }
        if (strpos($available_sets, 's') !== false) {
            $sets[] = '!@#$%&*?';
        }

        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++) {
            $password .= $all[array_rand($all)];
        }

        $password = str_shuffle($password);

        if (!$add_dashes) {
            return $password;
        }

        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while (strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }
}
