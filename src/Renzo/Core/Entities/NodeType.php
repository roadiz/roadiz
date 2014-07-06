<?php 

namespace RZ\Renzo\Core\Entities;


use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Utils\StringHandler;
/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\NodeTypeRepository")
 * @Table(name="node_types", indexes={
 *     @index(name="visible_idx",         columns={"visible"}), 
 *     @index(name="newsletter_type_idx", columns={"newsletter_type"}), 
 *     @index(name="hiding_nodes_idx",    columns={"hiding_nodes"})
 * })
 */
class NodeType extends PersistableObject
{
	/**
	 * @Column(type="string", unique=true)
	 */
	private $name;
	/**
	 * @return [type] [description]
	 */
	public function getName() {
	    return $this->name;
	}
	/**
	 * @param [type] $newname [description]
	 */
	public function setName($name) {
	    $this->name = StringHandler::removeDiacritics($name);
	    $this->name = trim(preg_replace('#([^a-zA-Z])#', '', ucwords($this->name)));
	    
	    return $this;
	}

	/**
	 * @Column(name="display_name", type="string")
	 */
	private $displayName;
	/**
	 * @return string
	 */
	public function getDisplayName() {
	    return $this->displayName;
	}
	/**
	 * @param string $newname
	 */
	public function setDisplayName($displayName) {
	    $this->displayName = $displayName;
	
	    return $this;
	}

	/**
	 * @Column(type="text", nullable=true)
	 */
	private $description;
	/**
	 * @return string
	 */
	public function getDescription() {
	    return $this->description;
	}
	/**
	 * @param string $newname
	 */
	public function setDescription($description) {
	    $this->description = $description;
	
	    return $this;
	}

	/**
	 * @Column(type="boolean")
	 */
	private $visible = true;
	/**
	 * @return [type] [description]
	 */
	public function isVisible() {
	    return $this->visible;
	}
	/**
	 * @param [type] $newvisible [description]
	 */
	public function setVisible($visible) {
	    $this->visible = $visible;
	
	    return $this;
	}
	/**
	 * @Column(name="newsletter_type", type="boolean")
	 */
	private $newsletterType = false;
	/**
	 * @return [type] [description]
	 */
	public function isNewsletterType() {
	    return $this->newsletterType;
	}
	/**
	 * @param [type] $newnewsletterType [description]
	 */
	public function setNewsletterType($newsletterType) {
	    $this->newsletterType = $newsletterType;
	
	    return $this;
	}
	/**
	 * @Column(name="hiding_nodes",type="boolean")
	 */
	private $hidingNodes = false;
	/**
	 * @return [type] [description]
	 */
	public function isHidingNodes() {
	    return $this->hidingNodes;
	}
	/**
	 * @param [type] $newhideNodes [description]
	 */
	public function setHidingNodes($hidingNodes) {
	    $this->hidingNodes = $hidingNodes;
	
	    return $this;
	}


	/**
     * @OneToMany(targetEntity="NodeTypeField", mappedBy="nodeType", cascade={"ALL"})
     */
	private $fields;
	/**
	 * @return ArrayCollection
	 */
	public function getFields() {
	    return $this->fields;
	}

	/**
	 * 
	 */
	public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    public function getSourceEntityClassName()
    {
    	return 'NS'.ucwords($this->getName());
    }
    public function getSourceEntityTableName()
    {
    	return 'ns_'.strtolower($this->getName());
    }

    public static function getGeneratedEntitiesNamespace()
    {
    	return 'GeneratedNodeSources';
    }

    public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getName().
			" — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
	}
	public function getFieldsSummary()
	{
		$text = "|".PHP_EOL;
		foreach ($this->getFields() as $field) {
			$text .= "|--- ".$field->getOneLineSummary();
		}
		return $text;
	}

	/**
	 * 
	 * @return NodeTypeHandler
	 */
	public function getHandler()
	{
		return new NodeTypeHandler( $this );
	}
}