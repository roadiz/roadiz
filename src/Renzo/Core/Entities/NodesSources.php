<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 */
class NodesSources {

	/**
	 * @Id @ManyToOne(targetEntity="Node")
	 */
	private $node;

	/**
	 * @Id @Column(type="integer", name="source_id")
	 */
	private $source_id;

	/**
	 * @return [type] [description]
	 */
	public function getSourceId() {
	    return $this->source_id;
	}
	/**
	 * @param [type] $newsource_id [description]
	 */
	public function setSourceId($source_id) {
	    $this->source_id = $source_id;
	
	    return $this;
	}

	/**
	 * @Id @ManyToOne(targetEntity="Translation")
	 */
	private $translation;
	/**
	 * @return [type] [description]
	 */
	public function getTranslation() {
	    return $this->translation;
	}
	/**
	 * @param [type] $newtranslation [description]
	 */
	public function setTranslation($translation) {
	    $this->translation = $translation;
	
	    return $this;
	}


	public function __construct( Node $node, PersistableObject $source, Translation $translation){
		$this->node = $node;
		$this->translation = $translation;
		// Be careful to use source
		$this->source_id = $source->getId();
	}
}
