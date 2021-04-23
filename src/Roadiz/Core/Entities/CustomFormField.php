<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;

/**
 * CustomFormField entities are used to create CustomForms with
 * custom data structure.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="custom_form_fields", uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"name", "custom_form_id"})
 * }, indexes={
*      @ORM\Index(columns={"position"}),
*      @ORM\Index(columns={"group_name"}),
*      @ORM\Index(columns={"type"}),
 *     @ORM\Index(columns={"custom_form_id", "position"}, name="cfield_customform_position")
*  })
 * @ORM\HasLifecycleCallbacks
 */
class CustomFormField extends AbstractField
{
    /**
     * Associates custom form field type to a readable string.
     *
     * These string will be used as translation key.
     *
     * @var array
     */
    public static $typeToHuman = [
        AbstractField::STRING_T => 'string.type',
        AbstractField::DATETIME_T => 'date-time.type',
        AbstractField::DATE_T => 'date.type',
        AbstractField::TEXT_T => 'text.type',
        AbstractField::MARKDOWN_T => 'markdown.type',
        AbstractField::BOOLEAN_T => 'boolean.type',
        AbstractField::INTEGER_T => 'integer.type',
        AbstractField::DECIMAL_T => 'decimal.type',
        AbstractField::EMAIL_T => 'email.type',
        AbstractField::ENUM_T => 'single-choice.type',
        AbstractField::MULTIPLE_T => 'multiple-choice.type',
        AbstractField::COUNTRY_T => 'country.type',
        AbstractField::DOCUMENTS_T => 'documents.type',
    ];
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm", inversedBy="fields")
     * @ORM\JoinColumn(name="custom_form_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $customForm = null;
    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\CustomFormFieldAttribute", mappedBy="customFormField")
     * @var Collection<CustomFormFieldAttribute>
     */
    private $customFormFieldAttributes;
    /**
     * @ORM\Column(name="field_required", type="boolean", nullable=false, options={"default" = false})
     * @var bool
     */
    private $required = false;

    /**
     * CustomFormField constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->customFormFieldAttributes = new ArrayCollection();
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        parent::setLabel($label);
        $this->setName($label);

        return $this;
    }

    /**
     * @return CustomForm|null
     */
    public function getCustomForm(): ?CustomForm
    {
        return $this->customForm;
    }

    /**
     * @param CustomForm|null $customForm
     *
     * @return $this
     */
    public function setCustomForm(CustomForm $customForm = null): CustomFormField
    {
        $this->customForm = $customForm;
        if (null !== $customForm) {
            $this->customForm->addField($this);
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCustomFormFieldAttribute(): Collection
    {
        return $this->customFormFieldAttributes;
    }

    /**
     * @return boolean $isRequired
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param boolean $required
     *
     * @return $this
     */
    public function setRequired(bool $required): CustomFormField
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return string
     */
    public function getOneLineSummary(): string
    {
        return $this->__toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getId() . " — " . $this->getName() . " — " . $this->getLabel() . PHP_EOL;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->customForm = null;
            $this->customFormFieldAttributes = new ArrayCollection();
        }
    }
}
