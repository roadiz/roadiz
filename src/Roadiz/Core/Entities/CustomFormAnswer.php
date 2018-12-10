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
 * @file CustomFormAnswer.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Entities;

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
     * @var \DateTime|null
     */
    private $submittedAt = null;
    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\CustomFormFieldAttribute",
     *            mappedBy="customFormAnswer",
     *            cascade={"ALL"})
     * @var ArrayCollection
     */
    private $answerFields;
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm",
     *           inversedBy="customFormAnswers")
     * @ORM\JoinColumn(name="custom_form_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomForm
     **/
    private $customForm;

    /**
     * Create a new empty CustomFormAnswer according to given node-type.
     */
    public function __construct()
    {
        $this->answerFields = new ArrayCollection();
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
     * @return ArrayCollection
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
    public function setSubmittedAt(\DateTime $submittedAt = null): CustomFormAnswer
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }

    /**
     * @param bool $namesAsKeys Use fields name as key. Default: true
     * @return array
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
