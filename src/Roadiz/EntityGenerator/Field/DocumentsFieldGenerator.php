<?php
declare(strict_types=1);

namespace RZ\Roadiz\EntityGenerator\Field;

class DocumentsFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return \\'.$this->options['document_class'].'[] Documents array
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources", "nodes_sources_documents", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @Serializer\SerializedName("'.$this->field->getVarName().'")
     */
    public function '.$this->field->getGetterName().'()
    {
        if (null === $this->' . $this->field->getVarName() . ') {
            if (null !== $this->objectManager) {
                $this->' . $this->field->getVarName() . ' = $this->objectManager
                    ->getRepository(\\'.$this->options['document_class'].'::class)
                    ->findByNodeSourceAndField(
                        $this,
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
     * @param \\'.$this->options['document_class'].' $document
     *
     * @return $this
     */
    public function add'.ucfirst($this->field->getVarName()).'(\\'.$this->options['document_class'].' $document)
    {
        $field = $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getName().'");
        if (null !== $field) {
            $nodeSourceDocument = new \\'.$this->options['document_proxy_class'].'(
                $this,
                $document,
                $field
            );
            $this->objectManager->persist($nodeSourceDocument);
            $this->addDocumentsByFields($nodeSourceDocument);
            $this->' . $this->field->getVarName() . ' = null;
        }
        return $this;
    }'.PHP_EOL;
    }
}
