<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AbstractHuman.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\AbstractDateTimed;

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