<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="nodes_sources")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 */
class NodesSources extends PersistableObject {

	/**
	 * @ManyToOne(targetEntity="Node")
	 */
	private $node;

	/**
	 * @return Node
	 */
	public function getNode() {
	    return $this->node;
	}
	
	/**
	 * @param Node $newnode 
	 */
	public function setNode($node) {
	    $this->node = $node;
	
	    return $this;
	}


	/**
	 * @ManyToOne(targetEntity="Translation")
	 */
	private $translation;
	/**
	 * @return Translation
	 */
	public function getTranslation() {
	    return $this->translation;
	}
	/**
	 * @param Translation $newtranslation
	 */
	public function setTranslation($translation) {
	    $this->translation = $translation;
	
	    return $this;
	}


	public function __construct( Node $node, Translation $translation){
		$this->node = $node;
		$this->translation = $translation;
	}
}
