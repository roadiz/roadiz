<?php 

namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\Persistable;
use RZ\Renzo\Core\AbstractEntities\Positioned;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Handlers\NodeTypeFieldHandler;

/**
 * @Entity
 * @Table(name="node_type_fields",  indexes={
 *     @index(name="visible_idx", columns={"visible"}), 
 *     @index(name="indexed_idx", columns={"indexed"})
 * })
 */
class NodeTypeField extends Positioned implements Persistable {

	const STRING_T = 		0;
	const DATETIME_T = 		1;
	const TEXT_T = 			2;
	const RICHTEXT_T = 		3;
	const MARKDOWN_T = 		4;
	const BOOLEAN_T = 		5;
	const INTEGER_T = 		6;
	const DECIMAL_T = 		7;
	const EMAIL_T = 		8;
	const DOCUMENTS_T = 	9;
	const PASSWORD_T = 		10;
	const COLOUR_T = 		11;
	const GEOTAG_T = 		12;
	const NODE_T = 			13;
	const USER_T = 			14;
	const ENUM_T = 			15;
	const CHILDREN_T = 		16;
	const SURVEY_T = 		17;
	const MULTIPLE_T = 		18;
	const RADIO_GROUP_T = 	19;
	const CHECK_GROUP_T = 	20;
	const MULTI_GEOTAG_T =  21;


	static $typeToHuman = array(
		NodeTypeField::STRING_T =>   'string',
		NodeTypeField::DATETIME_T => 'date-time',
		NodeTypeField::TEXT_T =>     'text',
		NodeTypeField::MARKDOWN_T => 'markdown',
		NodeTypeField::BOOLEAN_T =>  'boolean',
		NodeTypeField::INTEGER_T =>  'integer',
		NodeTypeField::DECIMAL_T =>  'decimal',
		NodeTypeField::EMAIL_T =>    'email',
		NodeTypeField::ENUM_T =>     'single-choice',
		NodeTypeField::MULTIPLE_T => 'multiple-choice',
	);
	static $typeToDoctrine = array(
		NodeTypeField::STRING_T =>   'string',
		NodeTypeField::DATETIME_T => 'datetime',
		NodeTypeField::TEXT_T =>     'text',
		NodeTypeField::MARKDOWN_T => 'text',
		NodeTypeField::BOOLEAN_T =>  'boolean',
		NodeTypeField::INTEGER_T =>  'integer',
		NodeTypeField::DECIMAL_T =>  'decimal',
		NodeTypeField::EMAIL_T =>    'string',
		NodeTypeField::ENUM_T =>     'string',
		NodeTypeField::MULTIPLE_T => 'simple_array',
	);
	static $typeToForm = array(
		NodeTypeField::STRING_T =>   'text',
		NodeTypeField::DATETIME_T => 'datetime',
		NodeTypeField::TEXT_T =>     'textarea',
		NodeTypeField::MARKDOWN_T => 'markdown',
		NodeTypeField::BOOLEAN_T =>  'checkbox',
		NodeTypeField::INTEGER_T =>  'integer',
		NodeTypeField::DECIMAL_T =>  'decimal',
		NodeTypeField::EMAIL_T =>    'email',
		NodeTypeField::ENUM_T =>     'enumeration',
		NodeTypeField::MULTIPLE_T => 'multiple_enumeration',
	);


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
	 * @return string
	 */
	public function getName() {
	    return $this->name;
	}
	
	/**
	 * @param string $newname
	 */
	public function setName($name) {
		$this->name = StringHandler::variablize($name);
	
	    return $this;
	}
	/**
	 * @Column(type="string")
	 */
	private $label;

	/**
	 * @return string
	 */
	public function getLabel() {
	    return $this->label;
	}
	
	/**
	 * @param string $newlabel
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
	 * @return string
	 */
	public function getDescription() {
	    return $this->description;
	}
	
	/**
	 * @param string $newdescription
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


	public function getGetterName()
	{
		return 'get'.str_replace('_', '', ucwords($this->getName()));
	}
	public function getSetterName()
	{
		return 'set'.str_replace('_', '', ucwords($this->getName()));
	}

	public function getHandler()
	{
		return new NodeTypeFieldHandler( $this );
	}

	public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getName()." — ".$this->getLabel().
			" — Indexed : ".($this->isIndexed()?'true':'false').PHP_EOL;
	}
}