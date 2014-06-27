<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="url_aliases")
 */
class UrlAlias extends PersistableObject
{
	
	/**
	 * @Column(type="string", unique=true)
	 */
	private $alias;
	/**
	 * @return
	 */
	public function getAlias() {
	    return $this->alias;
	}
	/**
	 * @param $newnodeName 
	 */
	public function setAlias($alias) {
	    $this->alias = $alias;
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Node")
	 * @JoinColumn(name="node_id", referencedColumnName="id")
	 */
	private $node;
	/**
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	public function getNode() {
	    return $this->node;
	}
	/**
	 * @param RZ\Renzo\Core\Entities\Node $newnode
	 */
	public function setNode($node) {
	    $this->node = $node;
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Translation")
	 * @JoinColumn(name="translation_id", referencedColumnName="id")
	 */
	private $translation;
	/**
	 * @return RZ\Renzo\Core\Entities\Translation
	 */
	public function getTranslation() {
	    return $this->translation;
	}
	/**
	 * @param RZ\Renzo\Core\Entities\Translation $translation
	 */
	public function setTranslation($translation) {
	    $this->translation = $translation;
	
	    return $this;
	}

	/**
	 * 
	 */
	public function __construct()
    {
    	parent::__construct();
    }
}