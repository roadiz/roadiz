<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Class NonVirtualFieldGenerator.
 *
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class NonVirtualFieldGenerator extends AbstractFieldGenerator
{
    /**
     * Generate PHP annotation block for Doctrine table indexes.
     *
     * @return string
     */
    public function getFieldIndex(): string
    {
        if ($this->field->isIndexed()) {
            return '@ORM\Index(columns={"'.$this->field->getName().'"})';
        }

        return '';
    }

    /**
     * @return mixed
     */
    protected function getDoctrineType()
    {
        if ($this->field->getType() === NodeTypeField::MULTI_PROVIDER_T &&
            $this->options[AbstractFieldGenerator::USE_NATIVE_JSON] === true) {
            return 'json';
        }
        return NodeTypeField::$typeToDoctrine[$this->field->getType()];
    }

    /**
     * @inheritDoc
     */
    public function getFieldAnnotation(): string
    {
        $serializationType = '';
        $exclusion = $this->excludeFromSerialization() ?
            '@Serializer\Exclude()' :
            '@Serializer\Groups({"nodes_sources", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})';
        $ormParams = [
            'type' => '"' . $this->getDoctrineType() . '"',
            'nullable' => 'true',
            'name' => '"' . $this->field->getName() . '"',
        ];

        if ($this->field->getType() == NodeTypeField::DECIMAL_T) {
            $ormParams['precision'] = 18;
            $ormParams['scale'] = 3;
            $serializationType = '@Serializer\Type("double")';
        } elseif ($this->field->getType() == NodeTypeField::BOOLEAN_T) {
            $ormParams['nullable'] = 'false';
            $ormParams['options'] = '{"default" = false}';
            $serializationType = '@Serializer\Type("boolean")';
        } elseif ($this->field->getType() == NodeTypeField::INTEGER_T) {
            $serializationType = '@Serializer\Type("integer")';
        }

        return '
    /**
     * ' . implode("\n     * ", $this->getFieldAutodoc()) .'
     *
     * @Gedmo\Versioned
     * @ORM\Column(' . static::flattenORMParameters($ormParams) . ')
     * ' . $exclusion . '
     * ' . $serializationType . '
     */'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDeclaration(): string
    {
        if ($this->field->getType() === NodeTypeField::BOOLEAN_T) {
            return '    private $'.$this->field->getVarName().' = false;'.PHP_EOL;
        } elseif ($this->field->getType() === NodeTypeField::INTEGER_T) {
            return '    private $'.$this->field->getVarName().' = 0;'.PHP_EOL;
        } else {
            return '    private $'.$this->field->getVarName().';'.PHP_EOL;
        }
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        $assignation = '$this->'.$this->field->getVarName();

        return '
    /**
     * @return mixed
     */
    public function '.$this->field->getGetterName().'()
    {
        return '.$assignation.';
    }'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSetter(): string
    {
        $assignation = '$'.$this->field->getVarName();

        if ($this->field->getType() === NodeTypeField::BOOLEAN_T) {
            $assignation = '(boolean) $'.$this->field->getVarName();
        }
        if ($this->field->getType() === NodeTypeField::INTEGER_T) {
            $assignation = '(int) $'.$this->field->getVarName();
        }
        if ($this->field->getType() === NodeTypeField::DECIMAL_T) {
            $assignation = '(double) $'.$this->field->getVarName();
        }

        return '
    /**
     * @param mixed $'.$this->field->getVarName().'
     *
     * @return $this
     */
    public function '.$this->field->getSetterName().'($'.$this->field->getVarName().')
    {
        $this->'.$this->field->getVarName().' = '.$assignation.';

        return $this;
    }'.PHP_EOL;
    }
}
