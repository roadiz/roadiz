<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\Entities\NodeTypeField;

abstract class AbstractFieldGenerator
{
    const USE_NATIVE_JSON = 'use_native_json';

    /**
     * @var NodeTypeField
     */
    protected $field;
    /**
     * @var array
     */
    protected $options;

    /**
     * @param NodeTypeField $field
     * @param array $options
     */
    public function __construct(NodeTypeField $field, array $options = [])
    {
        $this->field = $field;
        $this->options = $options;
    }

    /**
     * @param array $ormParams
     *
     * @return string
     */
    public static function flattenORMParameters(array $ormParams): string
    {
        $flatParams = [];
        foreach ($ormParams as $key => $value) {
            $flatParams[] = $key . '=' . $value;
        }

        return implode(', ', $flatParams);
    }

    /**
     * Generate PHP code for current doctrine field.
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->getFieldAnnotation().
            $this->getFieldDeclaration().
            $this->getFieldGetter().
            $this->getFieldAlternativeGetter().
            $this->getFieldSetter().PHP_EOL;
    }

    /**
     * @return array<string>
     */
    protected function getFieldAutodoc(): array
    {
        $docs = [
            $this->field->getLabel().'.',
            ''
        ];
        if (!empty($this->field->getDescription())) {
            $docs[] = $this->field->getDescription().'.';
        }
        if (!empty($this->field->getDefaultValues())) {
            $docs[] = 'Default values: ' . str_replace("\n", "\n     *     ", $this->field->getDefaultValues());
        }
        if (!empty($this->field->getGroupName())) {
            $docs[] = 'Group: ' . $this->field->getGroupName().'.';
        }
        return $docs;
    }

    /**
     * @return string
     */
    protected function getFieldAnnotation(): string
    {
        return '
    /**
     * ' . implode("\n     * ", $this->getFieldAutodoc()) .'
     *
     * (Virtual field, this var is a buffer)
     * @Serializer\Exclude
     */'.PHP_EOL;
    }

    /**
     * Generate PHP property declaration block.
     */
    protected function getFieldDeclaration(): string
    {
        /*
         * Buffer var to get referenced entities (documents, nodes, cforms, doctrine entities)
         */
        return '    private $'.$this->field->getVarName().';'.PHP_EOL;
    }

    /**
     * Generate PHP alternative getter method block.
     *
     * @return string
     */
    abstract protected function getFieldGetter(): string;

    /**
     * Generate PHP alternative getter method block.
     *
     * @return string
     */
    protected function getFieldAlternativeGetter(): string
    {
        return '';
    }

    /**
     * Generate PHP setter method block.
     *
     * @return string
     */
    protected function getFieldSetter(): string
    {
        return '';
    }

    /**
     * Generate PHP annotation block for Doctrine table indexes.
     *
     * @return string
     */
    public function getFieldIndex(): string
    {
        return '';
    }

    /**
     * Generate PHP property initialization for class constructor.
     *
     * @return string
     */
    public function getFieldConstructorInitialization(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    protected function excludeFromSerialization(): bool
    {
        return false;
    }
}
