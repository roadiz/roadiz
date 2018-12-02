<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file EntityGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * Class EntityGenerator
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class EntityGenerator
{
    /**
     * @var NodeType
     */
    private $nodeType;

    /**
     * @var array
     */
    private $fieldGenerators;

    /**
     * EntityGenerator constructor.
     * @param NodeType $nodeType
     */
    public function __construct(NodeType $nodeType)
    {
        $this->nodeType = $nodeType;
        $this->fieldGenerators = [];

        /** @var NodeTypeField $field */
        foreach ($this->nodeType->getFields() as $field) {
            $this->fieldGenerators[] = $this->getFieldGenerator($field);
        }
        $this->fieldGenerators = array_filter($this->fieldGenerators);
    }

    /**
     * @param NodeTypeField $field
     * @return AbstractFieldGenerator|null
     */
    protected function getFieldGenerator(NodeTypeField $field): ?AbstractFieldGenerator
    {
        if ($field->getType() === AbstractField::YAML_T) {
            return new YamlFieldGenerator($field);
        }
        if ($field->getType() === AbstractField::COLLECTION_T) {
            return new CollectionFieldGenerator($field);
        }
        if ($field->getType() === AbstractField::CUSTOM_FORMS_T) {
            return new CustomFormsFieldGenerator($field);
        }
        if ($field->getType() === AbstractField::DOCUMENTS_T) {
            return new DocumentsFieldGenerator($field);
        }
        if ($field->getType() === AbstractField::MANY_TO_ONE_T) {
            return new ManyToOneFieldGenerator($field);
        }
        if ($field->getType() === AbstractField::MANY_TO_MANY_T) {
            return new ManyToManyFieldGenerator($field);
        }
        if ($field->getType() === AbstractField::NODES_T) {
            return new NodesFieldGenerator($field);
        }
        if (!$field->isVirtual()) {
            return new NonVirtualFieldGenerator($field);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getClassContent(): string
    {
        return $this->getClassHeader().
            $this->getClassAnnotations().
            $this->getClassBody();
    }

    /**
     * @return string
     */
    protected function getClassBody(): string
    {
        return 'class '.$this->nodeType->getSourceEntityClassName().' extends NodesSources
{
    ' . $this->getClassProperties() . $this->getClassConstructor() . $this->getClassMethods() . '
}'.PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getClassHeader(): string
    {
        /*
         * BE CAREFUL, USE statements are required for field generators which
         * are using ::class syntax!
         */
        return '<?php
/*
 * THIS IS A GENERATED FILE, DO NOT EDIT IT
 * IT WILL BE RECREATED AT EACH NODE-TYPE UPDATE
 */
namespace '.NodeType::getGeneratedEntitiesNamespace().';

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\Mapping as ORM;'.PHP_EOL.PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getClassAnnotations(): string
    {
        $indexes = [];
        /** @var AbstractFieldGenerator $fieldGenerator */
        foreach ($this->fieldGenerators as $fieldGenerator) {
            $indexes[] = $fieldGenerator->getFieldIndex();
        }
        $indexes = array_filter($indexes);
        return '
/**
 * DO NOT EDIT
 * Generated custom node-source type by Roadiz.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesSourcesRepository")
 * @ORM\Table(name="'.$this->nodeType->getSourceEntityTableName().'", indexes={'.implode(',', $indexes).'})
 */'.PHP_EOL;
    }

    /**
     * @return string
     */
    protected function getClassProperties(): string
    {
        $fieldsArray = [];
        /** @var AbstractFieldGenerator $fieldGenerator */
        foreach ($this->fieldGenerators as $fieldGenerator) {
            $fieldsArray[] = $fieldGenerator->getField();
        }
        $fieldsArray = array_filter($fieldsArray);

        return implode('', $fieldsArray);
    }

    /**
     * @return string
     */
    protected function getClassConstructor(): string
    {
        $constructorArray = [];
        /** @var AbstractFieldGenerator $fieldGenerator */
        foreach ($this->fieldGenerators as $fieldGenerator) {
            $constructorArray[] = $fieldGenerator->getFieldConstructorInitialization();
        }
        $constructorArray = array_filter($constructorArray);

        if (count($constructorArray) > 0) {
            return '
    public function __construct(Node $node, Translation $translation) 
    {
        parent::__construct($node, $translation);
        
        '.implode(PHP_EOL, $constructorArray).'
    }'.PHP_EOL;
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getClassMethods(): string
    {
        return '    
    public function __toString()
    {
        return \'['.$this->nodeType->getSourceEntityClassName().'] \' . parent::__toString();
    }';
    }
}
