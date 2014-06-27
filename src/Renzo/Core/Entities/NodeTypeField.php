<?php 

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\Persistable;
use RZ\Renzo\Core\AbstractEntities\Positioned;

/**
 * @Entity
 * @Table(name="node_type_fields")
 */
class NodeTypeField extends Positioned implements Persistable {

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
     * @ManyToOne(targetEntity="NodeType", inversedBy="fields")
     */
	private $nodeType;

	/**
	 * @return [type] [description]
	 */
	public function getNodeType() {
	    return $this->nodeType;
	}
	/**
	 * @param [type] $newnodeType [description]
	 */
	public function setNodeType($nodeType) {
	    $this->nodeType = $nodeType;
	
	    return $this;
	}

	/**
	 * @Column(type="string")
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
	private $label;

	/**
	 * @return [type] [description]
	 */
	public function getLabel() {
	    return $this->label;
	}
	
	/**
	 * @param [type] $newlabel [description]
	 */
	public function setLabel($label) {
	    $this->label = $label;
	
	    return $this;
	}
	/**
	 * @Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @return [type] [description]
	 */
	public function getDescription() {
	    return $this->description;
	}
	
	/**
	 * @param [type] $newdescription [description]
	 */
	public function setDescription($description) {
	    $this->description = $description;
	
	    return $this;
	}
	/**
	 * @Column(type="boolean")
	 */
	private $indexed = false;
	public function isIndexed() {
	    return $this->indexed;
	}
	public function setIndexed($indexed) {
	    $this->indexed = $indexed;
	
	    return $this;
	}
	/**
	 * @Column(type="boolean")
	 */
	private $visible = true;
	public function isVisible() {
	    return $this->visible;
	}
	public function setVisible($visible) {
	    $this->visible = $visible;
	
	    return $this;
	}
	
	const STRING_T =   0;
	const MARKDOWN_T = 1;
	const TEXT_T =     2;
	const INTEGER_T =  3;
	const BOOLEAN_T =  4;

	static $typeToHuman = array(
		self::STRING_T =>   'string',
		self::MARKDOWN_T => 'markdown',
		self::TEXT_T =>     'text',
		self::INTEGER_T =>  'integer',
		self::BOOLEAN_T =>  'boolean',
	);
	static $typeToDoctrine = array(
		self::STRING_T =>   'string',
		self::MARKDOWN_T => 'text',
		self::TEXT_T =>     'text',
		self::INTEGER_T =>  'integer',
		self::BOOLEAN_T =>  'boolean',
	);
	static $typeToForm = array(
		self::STRING_T =>   'text',
		self::MARKDOWN_T => 'textarea',
		self::TEXT_T =>     'textarea',
		self::INTEGER_T =>  'integer',
		self::BOOLEAN_T =>  'checkbox',
	);

	/**
	 * @Column(type="integer")
	 */
	private $type = NodeTypeField::STRING_T;

	/**
	 * @return [type] [description]
	 */
	public function getType() {
	    return $this->type;
	}

	public function getTypeName()
	{
		return static::$typeToHuman[$this->type];
	}
	
	/**
	 * @param [type] $newtype [description]
	 */
	public function setType($type) {
	    $this->type = $type;
	
	    return $this;
	}

	public function generateSourceFieldIndex()
	{
		return '@Index(name="'.$this->getName().'_idx", columns={"'.$this->getName().'"})';
	}

	public function generateSourceField(){


		$var = 'private $'.$this->getName().';';
		if ($this->getType() === static::BOOLEAN_T) {
			$var = 'private $'.$this->getName().' = false;';
		}
		if ($this->getType() === static::INTEGER_T) {
			$var = 'private $'.$this->getName().' = 0;';
		}

		return '
	/**
	 * @Column(type="'.static::$typeToDoctrine[$this->getType()].'", nullable=true )
	 */
	'.$var.'
	public function get'.ucwords($this->getName()).'() {
	    return $this->'.$this->getName().';
	}
	public function set'.ucwords($this->getName()).'($'.$this->getName().') {
	    $this->'.$this->getName().' = $'.$this->getName().';
	
	    return $this;
	}'.PHP_EOL;

	}

	public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getName()." — ".$this->getLabel().
			" — Indexed : ".($this->isIndexed()?'true':'false').PHP_EOL;
	}
}