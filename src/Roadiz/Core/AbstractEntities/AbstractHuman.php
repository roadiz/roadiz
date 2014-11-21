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
 * @file AbstractHuman.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\AbstractEntities;

use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;

/**
 * Abstract entity for any Human-like objects.
 *
 * This class can be extended for *Users*, *Subscribers*, etc.
 *
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class AbstractHuman extends AbstractDateTimed
{
    /**
     * @Column(type="string", unique=true)
     */
    protected $email;
    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $this->email = $email;
        }

        return $this;
    }

    /**
     * @Column(type="string", nullable=true)
     */
    protected $firstName;
    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }
    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @Column(type="string", nullable=true)
     */
    protected $lastName;
    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }
    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @Column(type="string", nullable=true)
     */
    protected $company;
    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }
    /**
     * @param string $company
     *
     * @return $this
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @Column(type="string", nullable=true)
     */
    protected $job;
    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }
    /**
     * @param string $job
     *
     * @return $this
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @var \DateTime
     * @Column(type="datetime", nullable=true)
     */
    protected $birthday;
    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }
    /**
     * @param \DateTime $birthday
     *
     * @return $this
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }
}
