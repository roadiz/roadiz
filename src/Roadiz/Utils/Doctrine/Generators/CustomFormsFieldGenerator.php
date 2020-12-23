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
     * @return \\'.$this->options['custom_form_class'].'[] CustomForm array
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources", "nodes_sources_custom_forms", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @Serializer\SerializedName("'.$this->field->getVarName().'")
     */
    public function '.$this->field->getGetterName().'()
    {
        if (null === $this->' . $this->field->getVarName() . ') {
            if (null !== $this->objectManager) {
                $this->' . $this->field->getVarName() . ' = $this->objectManager
                    ->getRepository(\\'.$this->options['custom_form_class'].'::class)
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

    /**
     * Generate PHP setter method block.
     *
     * @return string
     */
    protected function getFieldSetter(): string
    {
        return '
    /**
     * @param \\'.$this->options['custom_form_class'].' $customForm
     *
     * @return $this
     */
    public function add'.ucfirst($this->field->getVarName()).'(\\'.$this->options['custom_form_class'].' $customForm)
    {
        $field = $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getName().'");
        if (null !== $field) {
            $nodeCustomForm = new \\'.$this->options['custom_form_proxy_class'].'(
                $this->getNode(),
                $customForm,
                $field
            );
            $this->objectManager->persist($nodeCustomForm);
            $this->getNode()->addCustomForm($nodeCustomForm);
            $this->' . $this->field->getVarName() . ' = null;
        }
        return $this;
    }'.PHP_EOL;
    }
}
