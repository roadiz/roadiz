<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
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
	 * @Id @ManyToOne(targetEntity="Translation")
	 */
	private $translation;


	public function __construct( Node $node, PersistableObject $source, Translation $translation){
		$this->node = $node;
		$this->translation = $translation;
		// Be careful to use source
		$this->source_id = $source->getId();
	}
}
