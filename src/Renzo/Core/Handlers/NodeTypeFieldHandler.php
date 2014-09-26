<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypeFieldHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Serializers\NodeTypeFieldSerializer;

/**
 * Handle operations with node-type fields entities.
 */
class NodeTypeFieldHandler
{

    private $nodeTypeField = null;
    /**
     * @return NodeTypeField
     */
    public function getNodeTypeField()
    {
        return $this->nodeTypeField;
    }
    /**
     * @param NodeTypeField $nodeTypeField
     *
     * @return $this
     */
    public function setNodeTypeField($nodeTypeField)
    {
        $this->nodeTypeField = $nodeTypeField;

        return $this;
    }

    /**
     * Generate PHP annotation block for table indexes.
     *
     * @return string
     */
    public function generateSourceFieldIndex()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {
            return '@Index(name="'.$this->nodeTypeField->getName().'_idx", columns={"'.$this->nodeTypeField->getName().'"})';
        } else {
            return '';
        }
    }

    /**
     * Generate PHP code for current node-type field.
     *
     * @return string
     */
    public function generateSourceField()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {
            $var = 'private $'.$this->nodeTypeField->getName().';';
            if ($this->nodeTypeField->getType() === NodeTypeField::BOOLEAN_T) {
                $var = 'private $'.$this->nodeTypeField->getName().' = false;';
            }
            if ($this->nodeTypeField->getType() === NodeTypeField::INTEGER_T) {
                $var = 'private $'.$this->nodeTypeField->getName().' = 0;';
            }

            return '
    /**
     * @Column(type="'.NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()].'", nullable=true )
     */
    '.$var.PHP_EOL.$this->generateSourceGetter().PHP_EOL.$this->generateSourceSetter().PHP_EOL;

        }

        return '';
    }

    /**
     * Generate PHP code for current node-type field setter.
     *
     * @return string
     */
    protected function generateSourceSetter()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {

            $assignation = '$'.$this->nodeTypeField->getName();

            if ($this->nodeTypeField->getType() === NodeTypeField::BOOLEAN_T) {
                $assignation = '(boolean) $'.$this->nodeTypeField->getName();
            }
            if ($this->nodeTypeField->getType() === NodeTypeField::INTEGER_T) {
                $assignation = '(int) $'.$this->nodeTypeField->getName();
            }

            return '
    /**
     * @param mixed $'.$this->nodeTypeField->getName().'
     *
     * @return $this
     */
    public function '.$this->nodeTypeField->getSetterName().'($'.$this->nodeTypeField->getName().')
    {
        $this->'.$this->nodeTypeField->getName().' = '.$assignation.';

        return $this;
    }'.PHP_EOL;

        }

        return '';
    }

    /**
     * Generate PHP code for current node-type field setter.
     *
     * @return string
     */
    protected function generateSourceGetter()
    {
        if (NodeTypeField::$typeToDoctrine[$this->nodeTypeField->getType()] !== null) {

            $assignation = '$this->'.$this->nodeTypeField->getName();

            return '
    /**
     * @return mixed
     */
    public function '.$this->nodeTypeField->getGetterName().'()
    {
        return '.$assignation.';
    }'.PHP_EOL;

        }

        return '';
    }

    /**
     * Create a new node-type-field handler with node-type-field to handle.
     *
     * @param NodeTypeField $field
     */
    public function __construct(NodeTypeField $field)
    {
        $this->nodeTypeField = $field;
    }
}
