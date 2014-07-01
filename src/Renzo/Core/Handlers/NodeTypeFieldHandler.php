<?php 

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;


class NodeTypeFieldHandler {

	private $nodeTypeField = null;
	/**
	 * @return NodeTypeField
	 */
	public function getNodeTypeField() {
	    return $this->nodeTypeField;
	}
	/**
	 * @param NodeTypeField $newnodeTypeField
	 */
	public function setNodeTypeField($nodeTypeField) {
	    $this->nodeTypeField = $nodeTypeField;
	    return $this;
	}

	public function generateSourceFieldIndex()
	{
		return '@Index(name="'.$this->getNodeTypeField()->getName().'_idx", columns={"'.$this->getNodeTypeField()->getName().'"})';
	}

	public function generateSourceField(){


		$var = 'private $'.$this->getNodeTypeField()->getName().';';
		if ($this->getNodeTypeField()->getType() === NodeTypeField::BOOLEAN_T) {
			$var = 'private $'.$this->getNodeTypeField()->getName().' = false;';
		}
		if ($this->getNodeTypeField()->getType() === NodeTypeField::INTEGER_T) {
			$var = 'private $'.$this->getNodeTypeField()->getName().' = 0;';
		}

		return '
	/**
	 * @Column(type="'.NodeTypeField::$typeToDoctrine[$this->getNodeTypeField()->getType()].'", nullable=true )
	 */
	'.$var.'
	public function '.$this->getNodeTypeField()->getGetterName().'() {
	    return $this->'.$this->getNodeTypeField()->getName().';
	}
	public function '.$this->getNodeTypeField()->getSetterName().'($'.$this->getNodeTypeField()->getName().') {
	    $this->'.$this->getNodeTypeField()->getName().' = $'.$this->getNodeTypeField()->getName().';
	
	    return $this;
	}'.PHP_EOL;

	}


	public function __construct(NodeTypeField $field)
	{
		$this->nodeTypeField = $field;
	}
}