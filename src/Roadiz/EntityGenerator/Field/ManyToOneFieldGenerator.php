<?php
declare(strict_types=1);

namespace RZ\Roadiz\EntityGenerator\Field;

use Symfony\Component\Yaml\Yaml;

class ManyToOneFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @inheritDoc
     */
    public function getFieldAnnotation(): string
    {
        /*
         * Many Users have One Address.
         * @ORM\ManyToOne(targetEntity="Address")
         * @ORM\JoinColumn(name="address_id", referencedColumnName="id", onDelete="SET NULL")
         */
        $configuration = Yaml::parse($this->field->getDefaultValues() ?? '');
        $ormParams = [
            'name' => '"' . $this->field->getName() . '_id"',
            'referencedColumnName' => '"id"',
            'onDelete' => '"SET NULL"',
        ];
        return '
    /**
     * ' . implode("\n     * ", $this->getFieldAutodoc()) .'
     *
     * @Serializer\Groups({"nodes_sources", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @var \\' . $configuration['classname'] . '|null
     * @ORM\ManyToOne(targetEntity="'. $configuration['classname'] .'")
     * @ORM\JoinColumn(' . static::flattenORMParameters($ormParams) . ')
     */'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return \RZ\Roadiz\Core\AbstractEntities\AbstractEntity|null
     */
    public function '.$this->field->getGetterName().'()
    {
        return $this->' . $this->field->getVarName() . ';
    }'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSetter(): string
    {
        return '
    /**
     * @var \RZ\Roadiz\Core\AbstractEntities\AbstractEntity|null $'.$this->field->getVarName().'
     * @return $this
     */
    public function '.$this->field->getSetterName().'($'.$this->field->getVarName().' = null)
    {
        $this->'.$this->field->getVarName().' = $'.$this->field->getVarName().';

        return $this;
    }'.PHP_EOL;
    }
}
