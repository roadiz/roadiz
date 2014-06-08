<?php
namespace RZ\Renzo\AbstractEntities;

use RZ\Renzo\AbstractEntities\PersistableObject;
/**
* @MappedSuperclass
*/
abstract class DateTimed implements PersistableObject
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	private $id;
	public function getId()
	{
		return $this->id;
	}
	/**
	 * @Column(type="datetime", name="created_at")
	 */
	private $createdAt;

	/**
	 * @return [type] [description]
	 */
	public function getCreatedAt() {
	    return $this->createdAt;
	}
	
	/**
	 * @param [type] $newcreatedAt [description]
	 */
	public function setCreatedAt($createdAt) {
	    $this->createdAt = $createdAt;
	
	    return $this;
	}
	/**
	 * @Column(type="datetime", name="updated_at")
	 */
	private $updatedAt;

	/**
	 * @return [type] [description]
	 */
	public function getUpdatedAt() {
	    return $this->updatedAt;
	}
	
	/**
	 * @param [type] $newupdatedAt [description]
	 */
	public function setUpdatedAt($updatedAt) {
	    $this->updatedAt = $updatedAt;
	
	    return $this;
	}
	
	public function resetDates()
	{
		$this->setCreatedAt(new \DateTime("now"));
		$this->setUpdatedAt(new \DateTime("now"));

		return $this;
	}
}