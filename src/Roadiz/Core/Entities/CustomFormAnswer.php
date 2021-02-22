<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * CustomFormAnswer entities.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="custom_form_answers",  indexes={
 *     @ORM\Index(columns={"ip"}),
 *     @ORM\Index(columns={"submitted_at"})
 * })
 */
class CustomFormAnswer extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", name="ip")
     * @var string
     */
    private $ip = '';
    /**
     * @ORM\Column(type="datetime", name="submitted_at")
     * @var \DateTime
     */
    private $submittedAt;
    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\CustomFormFieldAttribute",
     *            mappedBy="customFormAnswer",
     *            cascade={"ALL"})
     * @var Collection<CustomFormFieldAttribute>
     */
    private $answerFields;
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm",
     *           inversedBy="customFormAnswers")
     * @ORM\JoinColumn(name="custom_form_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomForm|null
     **/
    private $customForm = null;

    /**
     * Create a new empty CustomFormAnswer according to given node-type.
     */
    public function __construct()
    {
        $this->answerFields = new ArrayCollection();
        $this->submittedAt = new \DateTime();
    }

    /**
     * @param CustomFormAnswer $field
     * @return $this
     */
    public function addAnswerField(CustomFormAnswer $field): CustomFormAnswer
    {
        if (!$this->getAnswers()->contains($field)) {
            $this->getAnswers()->add($field);
        }

        return $this;
    }

    /**
     * @return Collection<CustomFormFieldAttribute>
     */
    public function getAnswers()
    {
        return $this->answerFields;
    }

    /**
     * @param CustomFormAnswer $field
     *
     * @return $this
     */
    public function removeAnswerField(CustomFormAnswer $field): CustomFormAnswer
    {
        if ($this->getAnswers()->contains($field)) {
            $this->getAnswers()->removeElement($field);
        }

        return $this;
    }

    /**
     * @return CustomForm
     */
    public function getCustomForm(): CustomForm
    {
        return $this->customForm;
    }

    /**
     * @param CustomForm $customForm
     * @return $this
     */
    public function setCustomForm(CustomForm $customForm): CustomFormAnswer
    {
        $this->customForm = $customForm;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getId() . " — " . $this->getIp() .
        " — Submitted : " . ($this->getSubmittedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return $this
     */
    public function setIp(string $ip): CustomFormAnswer
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSubmittedAt(): ?\DateTime
    {
        return $this->submittedAt;
    }

    /**
     * @param \DateTime $submittedAt
     *
     * @return $this
     */
    public function setSubmittedAt(\DateTime $submittedAt): CustomFormAnswer
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        $attribute = $this->getAnswers()->filter(function (CustomFormFieldAttribute $attribute) {
            return $attribute->getCustomFormField()->isEmail();
        })->first();
        return $attribute ? (string) $attribute->getValue() : null;
    }

    /**
     * @param bool $namesAsKeys Use fields name as key. Default: true
     * @return array
     * @deprecated Use CustomFormAnswerSerializer instead
     */
    public function toArray($namesAsKeys = true): array
    {
        $answers = [];
        /** @var CustomFormFieldAttribute $answer */
        foreach ($this->answerFields as $answer) {
            $field = $answer->getCustomFormField();
            if ($namesAsKeys) {
                $answers[$field->getName()] = $answer->getValue();
            } else {
                $answers[] = [
                    'name' => $field->getName(),
                    'label' => $field->getLabel(),
                    'description' => $field->getDescription(),
                    'value' => $answer->getValue(),
                ];
            }
        }
        return $answers;
    }
}
