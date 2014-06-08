<?php 

namespace RZ\Renzo\Entities;


use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\AbstractEntities\PersistableObject;
/**
 * @Entity
 */
class NodeType implements PersistableObject
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
	    $this->name = $name;
	
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
	 * @Column(type="boolean")
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
	 * @Column(type="boolean")
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
    	return 'NS_'.ucwords($this->getName());
    }

    public function generateSourceEntityClass()
    {
    	$file = RENZO_ROOT.'/sources/GeneratedNodeSources/'.$this->getSourceEntityClassName().'.php';

    	if (!file_exists($file)) {

    		$fields = $this->getFields();
    		$fieldsArray = array();
    		$indexes = array();
    		foreach ($fields as $field) {
    			$fieldsArray[] = $field->generateSourceField();
    			if ($field->isIndexed()) {
    				$indexes[] = $field->generateSourceFieldIndex();
    			}
    		}

	    	$content = '<?php
/**
 * THIS IS A GENERATED FILE, DO NOT EDIT IT
 */
namespace GeneratedNodeSources;

use RZ\Renzo\AbstractEntities\PersistableObject;
/**
 * @Entity
 * @Table(name="'.$this->getSourceEntityTableName().'", indexes={'.implode(',', $indexes).'})
 */
class '.$this->getSourceEntityClassName().' implements PersistableObject
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
	'.implode('', $fieldsArray).'
}';
			file_put_contents($file, $content);
    	}
    	else {
    		echo "Source class “".$this->getSourceEntityClassName()."” already exists.".PHP_EOL;
    	}

		return $this;
    }
}