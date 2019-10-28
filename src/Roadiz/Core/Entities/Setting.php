<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file Setting.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\CMS\Forms\CssType;
use RZ\Roadiz\CMS\Forms\JsonType;
use RZ\Roadiz\CMS\Forms\MarkdownType;
use RZ\Roadiz\CMS\Forms\YamlType;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use JMS\Serializer\Annotation as Serializer;

/**
 * Settings entity are a simple key-value configuration system.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\SettingRepository")
 * @ORM\Table(name="settings", indexes={
 *     @ORM\Index(columns={"type"}),
 *     @ORM\Index(columns={"name"}),
 *     @ORM\Index(columns={"visible"})
 * })
 */
class Setting extends AbstractEntity
{
    /**
     * Associates custom form field type to a readable string.
     *
     * These string will be used as translation key.
     *
     * @var array
     * @Serializer\Exclude()
     */
    public static $typeToHuman = [
        AbstractField::STRING_T => 'string.type',
        AbstractField::DATETIME_T => 'date-time.type',
        AbstractField::TEXT_T => 'text.type',
        AbstractField::MARKDOWN_T => 'markdown.type',
        AbstractField::BOOLEAN_T => 'boolean.type',
        AbstractField::INTEGER_T => 'integer.type',
        AbstractField::DECIMAL_T => 'decimal.type',
        AbstractField::EMAIL_T => 'email.type',
        AbstractField::DOCUMENTS_T => 'documents.type',
        AbstractField::COLOUR_T => 'colour.type',
        AbstractField::JSON_T => 'json.type',
        AbstractField::CSS_T => 'css.type',
        AbstractField::YAML_T => 'yaml.type',
        AbstractField::ENUM_T => 'single-choice.type',
        AbstractField::MULTIPLE_T => 'multiple-choice.type',
    ];

    /**
     * Associates node-type field type to a Symfony Form type.
     *
     * @var array
     * @Serializer\Exclude()
     */
    public static $typeToForm = [
        AbstractField::STRING_T => TextType::class,
        AbstractField::DATETIME_T => DateTimeType::class,
        AbstractField::TEXT_T => TextareaType::class,
        AbstractField::MARKDOWN_T => MarkdownType::class,
        AbstractField::BOOLEAN_T => CheckboxType::class,
        AbstractField::INTEGER_T => IntegerType::class,
        AbstractField::DECIMAL_T => NumberType::class,
        AbstractField::EMAIL_T => EmailType::class,
        AbstractField::DOCUMENTS_T => FileType::class,
        AbstractField::COLOUR_T => TextType::class,
        AbstractField::JSON_T => JsonType::class,
        AbstractField::CSS_T => CssType::class,
        AbstractField::YAML_T => YamlType::class,
        AbstractField::ENUM_T => ChoiceType::class,
        AbstractField::MULTIPLE_T => ChoiceType::class,
    ];

    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"setting", "nodes_sources"})
     * @Serializer\Type("string")
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = trim(strtolower($name));
        $this->name = StringHandler::removeDiacritics($this->name);
        $this->name = preg_replace('#([^a-z])#', '_', $this->name);

        return $this;
    }

    /**
     * @var string|null
     * @ORM\Column(type="text", unique=false, nullable=true)
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return Setting
     */
    public function setDescription(?string $description): Setting
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"setting", "nodes_sources"})
     * @Serializer\Type("string")
     */
    private $value;
    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->getType() == NodeTypeField::BOOLEAN_T) {
            return (boolean) $this->value;
        }
        if ($this->getType() == NodeTypeField::DATETIME_T) {
            return new \DateTime($this->value);
        }
        if ($this->getType() == NodeTypeField::DOCUMENTS_T) {
            return (int) $this->value;
        }

        return $this->value;
    }
    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        if (($this->getType() === NodeTypeField::DATETIME_T || $this->getType() === NodeTypeField::DATE_T) &&
            $value instanceof \DateTime) {
            $this->value = $value->format('Y-m-d H:i:s'); // $value is instance of \DateTime
        } else {
            $this->value = $value;
        }

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("bool")
     */
    private $visible = true;
    /**
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }
    /**
     * @param boolean $visible
     *
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = (boolean) $visible;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\SettingGroup", inversedBy="settings", cascade={"persist", "merge"}, fetch="EAGER")
     * @ORM\JoinColumn(name="setting_group_id", referencedColumnName="id", onDelete="SET NULL")
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\SettingGroup")
     * @Serializer\Accessor(getter="getSettingGroup", setter="setSettingGroup")
     * @Serializer\AccessType("public_method")
     * @var SettingGroup
     */
    private $settingGroup;

    /**
     * @return SettingGroup
     */
    public function getSettingGroup(): ?SettingGroup
    {
        return $this->settingGroup;
    }
    /**
     * @param SettingGroup $settingGroup
     *
     * @return $this
     */
    public function setSettingGroup(?SettingGroup $settingGroup)
    {
        $this->settingGroup = $settingGroup;

        return $this;
    }

    /**
     * Value types.
     * Use NodeTypeField types constants.
     *
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("int")
     */
    private $type = NodeTypeField::STRING_T;
    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
    /**
     * @param integer $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = (int) $type;

        return $this;
    }

    /**
     * Available values for ENUM and MULTIPLE setting types.
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("string")
     */
    private $defaultValues = "";

    /**
     * @return string
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * @param string $defaultValues
     *
     * @return Setting
     */
    public function setDefaultValues($defaultValues)
    {
        $this->defaultValues = $defaultValues;

        return $this;
    }
}
