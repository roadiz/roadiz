<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

/**
 * Class DocumentsFieldGenerator
 *
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class DocumentsFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return array Documents array
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"nodes_sources", "nodes_sources_documents", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @Serializer\SerializedName("'.$this->field->getVarName().'")
     */
    public function '.$this->field->getGetterName().'()
    {
        if (null === $this->' . $this->field->getVarName() . ') {
            if (null !== $this->objectManager) {
                $this->' . $this->field->getVarName() . ' = $this->objectManager
                    ->getRepository(Document::class)
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
     * @param Document $document
     *
     * @return $this
     */
    public function add'.ucfirst($this->field->getVarName()).'(Document $document)
    {
        $field = $this->getNode()->getNodeType()->getFieldByName("'.$this->field->getName().'");
        if (null !== $field) {
            $nodeSourceDocument = new \RZ\Roadiz\Core\Entities\NodesSourcesDocuments(
                $this,
                $document,
                $field
            );
            if (!$this->hasNodesSourcesDocuments($nodeSourceDocument)) {
                $this->objectManager->persist($nodeSourceDocument);
                $this->addDocumentsByFields($nodeSourceDocument);
                $this->' . $this->field->getVarName() . ' = null;
            }
        }
        return $this;
    }'.PHP_EOL;
    }
}
