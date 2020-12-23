<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

/**
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class YamlFieldGenerator extends NonVirtualFieldGenerator
{
    /**
     * @inheritDoc
     */
    protected function excludeFromSerialization()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getFieldAlternativeGetter(): string
    {
        $assignation = '$this->'.$this->field->getVarName();
        return '
    /**
     * @return object|null
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @Serializer\SerializedName("'.$this->field->getVarName().'")
     */
    public function '.$this->field->getGetterName().'AsObject()
    {
        if (null !== '.$assignation.') {
            return Yaml::parse('.$assignation.');
        }
        return null;
    }'.PHP_EOL;
    }
}
