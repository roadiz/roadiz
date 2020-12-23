<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

/**
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class CustomFormsFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return array CustomForm array
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources", "nodes_sources_custom_forms", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @Serializer\SerializedName("'.$this->field->getVarName().'")
     */
    public function '.$this->field->getGetterName().'()
    {
        if (null === $this->' . $this->field->getVarName() . ') {
            if (null !== $this->objectManager) {
                $this->' . $this->field->getVarName() . ' = $this->objectManager
                    ->getRepository(CustomForm::class)
                    ->findByNodeAndField(
                        $this->getNode(),
                        $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getName().'")
                    );
            } else {
                $this->' . $this->field->getVarName() . ' = [];
            }
        }
        return $this->' . $this->field->getVarName() . ';
    }'.PHP_EOL;
    }
}
