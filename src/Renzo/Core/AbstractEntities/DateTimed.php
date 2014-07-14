<?php
namespace RZ\Renzo\Core\AbstractEntities;

use RZ\Renzo\Core\AbstractEntities\PersistableObject;
/**
* @MappedSuperclass
* @HasLifecycleCallbacks
*/
abstract class DateTimed extends PersistableObject
{
	/**
	 * @Column(type="datetime", name="created_at")
	 * @var \DateTime
	 */
	private $createdAt;

	/**
	 * @return \DateTime
	 */
	public function getCreatedAt() {
	    return $this->createdAt;
	}
	
	/**
	 * @param \DateTime $newcreatedAt
	 */
	public function setCreatedAt($createdAt) {
	    $this->createdAt = $createdAt;
	    return $this;
	}
	/**
	 * @Column(type="datetime", name="updated_at")
	 * @var \DateTime
	 */
	private $updatedAt;

	/**
	 * @return \DateTime [description]
	 */
	public function getUpdatedAt() {
	    return $this->updatedAt;
	}
	
	/**
	 * @param \DateTime $newupdatedAt
	 */
	public function setUpdatedAt($updatedAt) {
	    $this->updatedAt = $updatedAt;
	    return $this;
	}
	
	public function __construct()
	{
		parent::__construct();
	}

	/** 
	 * @PreUpdate
	 */
    public function preUpdate()
    {
        $this->setUpdatedAt(new \DateTime("now"));
    }
    /** 
     * @PrePersist
     */
    public function prePersist()
    {
    	$this->setUpdatedAt(new \DateTime("now"));
        $this->setCreatedAt(new \DateTime("now"));
    }
	
	public function resetDates()
	{
		$this->setCreatedAt(new \DateTime("now"));
		$this->setUpdatedAt(new \DateTime("now"));

		return $this;
	}
}