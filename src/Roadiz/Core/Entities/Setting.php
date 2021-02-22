<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\CMS\Forms\ColorType;
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
use Symfony\Component\String\UnicodeString;

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
        AbstractField::COLOUR_T => ColorType::class,
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
    private $name = '';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = trim(strtolower($name ?? ''));
        $this->name = (new UnicodeString($this->name))
            ->ascii()
            ->toString();
        $this->name = preg_replace('#([^a-z])#', '_', $this->name);

        return $this;
    }

    /**
     * @var string|null
     * @ORM\Column(type="text", unique=false, nullable=true)
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("string")
     */
    private $description = null;

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
    private $value = null;

    /**
     * Holds clear setting value after value is decoded by postLoad Doctrine event.
     *
     * READ ONLY: Not persisted value to hold clear value if setting is encrypted.
     *
     * @var string|null
     * @Serializer\Exclude()
     */
    private $clearValue = null;

    /**
     * @return string|null
     */
    public function getRawValue(): ?string
    {
        return $this->value;
    }

    /**
     * Getter for setting value OR clear value, if encrypted.
     *
     * @return bool|\DateTime|int|null
     * @throws \Exception
     */
    public function getValue()
    {
        if ($this->isEncrypted()) {
            $value = $this->clearValue;
        } else {
            $value = $this->value;
        }

        if ($this->getType() == NodeTypeField::BOOLEAN_T) {
            return (boolean) $value;
        }

        if (null !== $value) {
            if ($this->getType() == NodeTypeField::DATETIME_T) {
                return new \DateTime($value);
            }
            if ($this->getType() == NodeTypeField::DOCUMENTS_T) {
                return (int) $value;
            }
        }

        return $value;
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
            $this->value = $value->format('c'); // $value is instance of \DateTime
        } else {
            $this->value = $value;
        }

        return $this;
    }

    /**
     * Holds clear setting value after value is decoded by postLoad Doctrine event.
     *
     * @param string|null $clearValue
     *
     * @return Setting
     */
    public function setClearValue(?string $clearValue): Setting
    {
        $this->clearValue = $clearValue;

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
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("bool")
     */
    private $encrypted = false;

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /**
     * @param bool $encrypted
     *
     * @return Setting
     */
    public function setEncrypted(bool $encrypted): Setting
    {
        $this->encrypted = $encrypted;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\SettingGroup", inversedBy="settings", cascade={"persist", "merge"}, fetch="EAGER")
     * @ORM\JoinColumn(name="setting_group_id", referencedColumnName="id", onDelete="SET NULL")
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\SettingGroup")
     * @Serializer\Accessor(getter="getSettingGroup", setter="setSettingGroup")
     * @Serializer\AccessType("public_method")
     * @var SettingGroup|null
     */
    private $settingGroup;

    /**
     * @return SettingGroup|null
     */
    public function getSettingGroup(): ?SettingGroup
    {
        return $this->settingGroup;
    }
    /**
     * @param SettingGroup|null $settingGroup
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
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"setting"})
     * @Serializer\Type("string")
     */
    private $defaultValues;

    /**
     * @return string|null
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
