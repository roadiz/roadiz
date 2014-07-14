<?php 

namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\DateTimed;
/**
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class Human extends DateTimed
{
	/**
	 * @Column(type="string", unique=true)
	 */
	protected $email;
	/**
	 * @return string
	 */
	public function getEmail() {
	    return $this->email;
	}
	/**
	 * @param string $newemail
	 */
	public function setEmail($email) {
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
	public function getFirstName() {
	    return $this->firstName;
	}
	/**
	 * @param string $newemail 
	 */
	public function setFirstName($firstName) {

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
	public function getLastName() {
	    return $this->lastName;
	}
	/**
	 * @param string $newemail
	 */
	public function setLastName($lastName) {

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
	public function getCompany() {
	    return $this->company;
	}
	/**
	 * @param string $newemail
	 */
	public function setCompany($company) {

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
	public function getJob() {
	    return $this->job;
	}
	/**
	 * @param string $newemail
	 */
	public function setJob($job) {

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
	public function getBirthday() {
	    return $this->birthday;
	}
	/**
	 * @param \DateTime $newbirthday
	 */
	public function setBirthday($birthday) {

		$this->birthday = $birthday;
		return $this;
	}
}