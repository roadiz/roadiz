<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file CustomFormAnswer.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * CustomFormAnswer entities
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="custom_form_answers",  indexes={
 *     @ORM\Index(name="ip_customformanswer_idx", columns={"ip"}),
 *     @ORM\Index(name="submitted_customformanswer_idx", columns={"submitted_at"})
 * })
 */
class CustomFormAnswer extends AbstractEntity
{

    /**
     * @ORM\Column(type="string", name="ip")
     */
    private $ip;
    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
    /**
     * @param string $ip
     *
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @ORM\Column(type="datetime", name="submitted_at")
     */
    private $submittedAt = null;
    /**
     * @return \DateTime
     */
    public function getSubmittedAt()
    {
        return $this->submittedAt;
    }
    /**
     * @param \DateTime $home
     *
     * @return $this
     */
    public function setSubmittedAt($submittedAt)
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\CustomFormFieldAttribute",
     *            mappedBy="customFormAnswer",
     *            fetch="EXTRA_LAZY",
     *            cascade={"ALL"})
     * @var ArrayCollection
     */
    private $answerFields;

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
    public function addAnswerField($field)
    {
        if (!$this->getAnswers()->contains($field)) {
            $this->getAnswers()->add($field);
        }

        return $this;
    }
    /**
     * @param CustomFormAnswer $field
     *
     * @return $this
     */
    public function removeAnswerField(CustomFormAnswer $field)
    {
        if ($this->getAnswers()->contains($field)) {
            $this->getAnswers()->removeElement($field);
        }

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\CustomForm",
     *           inversedBy="customFormAnswers")
     * @ORM\JoinColumn(name="custom_form_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $customForm;

    public function setCustomForm($customForm)
    {
        $this->customForm = $customForm;
        return $this;
    }

    public function getCustomForm()
    {
        return $this->customForm;
    }

    /**
     * Create a new empty CustomFormAnswer according to given node-type.
     *
     * @param CustomFormAnswerType $nodeType
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getIp().
            " — Sumitted : ".($this->getSummittedTime()).PHP_EOL;
    }
    /**
     * @return string
     */
    public function getOneLineSourceSummary()
    {
        $text = "Source ".$this->getDefaultCustomFormAnswerSource()->getId().PHP_EOL;

        foreach ($this->getCustomFormAnswerType()->getFields() as $key => $field) {
            $getterName = 'get'.ucwords($field->getName());
            $text .= '['.$field->getLabel().']: '.$this->getDefaultCustomFormAnswerSource()->$getterName().PHP_EOL;
        }

        return $text;
    }
}
