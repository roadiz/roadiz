<?php 

namespace RZ\Renzo\Core\Entities;


use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\Persistable;
/**
 * @Entity
 */
class NodeType implements Persistable
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
	 * @Column(type="string")
	 */
	private $displayName;
	/**
	 * @return [type] [description]
	 */
	public function getDisplayName() {
	    return $this->displayName;
	}
	/**
	 * @param [type] $newname [description]
	 */
	public function setDisplayName($displayName) {
	    $this->displayName = $displayName;
	
	    return $this;
	}

	/**
	 * @Column(type="text")
	 */
	private $description;
	/**
	 * @return [type] [description]
	 */
	public function getDescription() {
	    return $this->description;
	}
	/**
	 * @param [type] $newname [description]
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
     * @OneToMany(targetEntity="NodeTypeField", mappedBy="nodeType", fetch="EXTRA_LAZY", cascade={"ALL"})
     */
	private $fields;
	/**
	 * @return ArrayCollection
	 */
	public function getFields() {
	    return $this->fields;
	}
	public function addField( NodeTypeField $field ) {
		$this->fields->add($field);
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

    public static function getGeneratedEntitiesNamespace()
    {
    	return 'GeneratedNodeSources';
    }

    public function generateSourceEntityClass()
    {
    	$folder = RENZO_ROOT.'/sources/'.static::getGeneratedEntitiesNamespace();
    	$file = $folder.'/'.$this->getSourceEntityClassName().'.php';

    	if (!file_exists($folder)) {
    		mkdir($folder, 0755, true);
    	}

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
namespace '.static::getGeneratedEntitiesNamespace().';

use RZ\Renzo\Core\AbstractEntities\PersistableObject;
/**
 * @Entity
 * @Table(name="'.$this->getSourceEntityTableName().'", indexes={'.implode(',', $indexes).'})
 */
class '.$this->getSourceEntityClassName().' extends PersistableObject
{

	'.implode('', $fieldsArray).'
}';
			file_put_contents($file, $content);
			return "Source class “".$this->getSourceEntityClassName()."” has been created.".PHP_EOL;
    	}
    	else {
    		return "Source class “".$this->getSourceEntityClassName()."” already exists.".PHP_EOL;
    	}

		return false;
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
}