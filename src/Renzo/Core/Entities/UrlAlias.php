<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\UrlAliasRepository")
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
		$this->alias = StringHandler::slugify($alias);
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\NodesSources", inversedBy="urlAliases")
	 * @JoinColumn(name="ns_id", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $nodeSource;
	/**
	 * @return RZ\Renzo\Core\Entities\NodesSources
	 */
	public function getNodeSource() {
	    return $this->nodeSource;
	}
	/**
	 * @param RZ\Renzo\Core\Entities\NodesSources $newnode
	 */
	public function setNodeSource($nodeSource) {
	    $this->nodeSource = $nodeSource;
	    return $this;
	}

	public function __construct( $nodeSource ){
		parent::__construct();


		$this->setNodeSource( $nodeSource );
	}
}