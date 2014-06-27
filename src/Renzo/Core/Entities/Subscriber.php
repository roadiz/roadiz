<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\Human;

/**
 * @Entity
 * @Table(name="subscribers")
 */
class Subscriber extends Human
{
	/**
	 * @Column(type="boolean")
	 */
	private $hardBounced = false;
	/**
	 * @return [type] [description]
	 */
	public function isHardBounced() {
	    return $this->hardBounced;
	}
	/**
	 * @param [type] $hardBounced [description]
	 */
	public function setHardBounced($hardBounced) {
	    $this->hardBounced = (boolean)$hardBounced;
	
	    return $this;
	}

	/**
	 * @Column(type="boolean")
	 */
	private $softBounced = false;
	/**
	 * @return [type] [description]
	 */
	public function isSoftBounced() {
	    return $this->softBounced;
	}
	/**
	 * @param [type] $hardBounced [description]
	 */
	public function setSoftBounced($softBounced) {
	    $this->softBounced = (boolean)$softBounced;
	
	    return $this;
	}

	/**
	 * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Tag")
	 * @JoinTable(name="subscribers_tags",
     *      joinColumns={@JoinColumn(name="subscriber_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="tag_id", referencedColumnName="id")}
     * )
	 * @var Doctrine\Common\Collections\ArrayCollection
	 */
	private $tags;
	/**
	 * @return Doctrine\Common\Collections\ArrayCollection
	 */
	public function getTags() {
	    return $this->tags;
	}

	/**
	 * 
	 */
	public function __construct()
    {
    	parent::__construct();

    	$this->tags = new ArrayCollection();
    }
}