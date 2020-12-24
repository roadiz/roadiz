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
 * @file UserHandlerTest.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Tests\SchemaDependentCase;

class UserHandlerTest extends SchemaDependentCase
{
    /**
     * @dataProvider encodeUserProvider
     * @param $userName
     * @param $email
     * @param $plainPassword
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testEncodeUser($userName, $email, $plainPassword)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get("em");

        /** @var \Symfony\Component\Security\Core\Encoder\EncoderFactory $encoderFactory */
        $encoderFactory = $this->get('userEncoderFactory');

        $user = new User();
        $user->setUsername($userName);
        $user->setEmail($email);
        $user->setPlainPassword($plainPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->assertTrue($encoderFactory->getEncoder($user)->isPasswordValid(
            $user->getPassword(),
            $plainPassword,
            $user->getSalt()
        ));

        $this->assertNotEmpty($user->getPassword());
        $this->assertNotEmpty($user->getSalt());
        $this->assertNotEmpty($user->getPictureUrl());
        $this->assertFalse($user->willSendCreationConfirmationEmail());
        $this->assertCount(1, $user->getRoles());
    }

    /**
     * @return array
     */
    public function encodeUserProvider()
    {
        return [
            ['phpunitUser001', 'phpunit-user@roadiz.io', 'my-very-very-strong-password'],
            ['phpunitUser002', 'phpunit-user2@roadiz.io', 'AvbT8jkc0SscLb'],
            ['phpunitUser003', 'phpunit-user3@roadiz.io', '6dSc4ZRGtJVq0g'],
        ];
    }
}
