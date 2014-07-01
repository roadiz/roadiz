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
     * @ManyToMany(targetEntity="Tag", inversedBy="subscribers")
     * @JoinTable(name="subscribers_tags")
     * @var ArrayCollection
     */
    private $tags = null;
    /**
     * @return ArrayCollection
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