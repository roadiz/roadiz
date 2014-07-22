<?php 

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\Handlers\NodesSourcesHandler;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="nodes_sources", uniqueConstraints={@UniqueConstraint(columns={"id","node_id", "translation_id"})})
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 */
class NodesSources extends PersistableObject {

	/**
	 * @ManyToOne(targetEntity="Node", inversedBy="nodeSources")
	 * @JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
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
	 * @JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
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

	/**
	 * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\UrlAlias", mappedBy="nodeSource", orphanRemoval=true, fetch="EXTRA_LAZY")
	 */
	private $urlAliases = null;
	/**
	 * @return ArrayCollection
	 */
	public function getUrlAliases() {
	    return $this->urlAliases;
	}

	/**
	 * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\NodesSourcesDocuments", mappedBy="nodeSource", orphanRemoval=true, fetch="EXTRA_LAZY")
	 */
	private $documentsByFields = null;
	public function getDocumentsByFields()
	{
		return $this->documentsByFields;
	}

	/**
	 * @return NodesSourcesHandler
	 */
	public function getHandler()
	{
		return new NodesSourcesHandler( $this );
	}


	public function __construct( Node $node, Translation $translation){
		$this->node = $node;
		$this->translation = $translation;
		$this->urlAliases = new ArrayCollection();
		$this->documentsByFields = new ArrayCollection();
	}
}
