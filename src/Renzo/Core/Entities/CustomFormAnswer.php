<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file CustomFormAnswer.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;

use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Entities\CustomFormFieldAttribute;

use RZ\Renzo\Core\Kernel;

/**
 * CustomFormAnswer entities
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="custom_form_answers",  indexes={
 *     @index(name="ip_customformanswer_idx", columns={"ip"}),
 *     @index(name="submitted_customformanswer_idx", columns={"submitted_at"})
 * })
 */
class CustomFormAnswer extends AbstractEntity
{

    /**
     * @Column(type="string", name="ip")
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
     * @Column(type="datetime", name="submitted_at")
     */
    private $submittedAt = null;
    /**
     * @return boolean
     */
    public function getSummittedTime()
    {
        return (boolean) $this->submittedAt;
    }
    /**
     * @param boolean $home
     *
     * @return $this
     */
    public function setSummittedTime($submittedAt)
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    /**
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\CustomFormFieldAttribute",
     *            mappedBy="customFormAnswer",
     *            fetch="EXTRA_LAZY")
     * @OrderBy({"position" = "ASC"})
     * @var ArrayCollection
     */
    private $answerField;

    /**
     * @return ArrayCollection
     */
    public function getAnswer()
    {
        return $this->answerField;
    }
    /**
     * @param CustomFormAnswer $field
     *
     * @return $this
     */
    public function addAnswerField($field)
    {
        if (!$this->getAnswer()->contains($field)) {
            $this->getAnswer()->add($field);
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
        if ($this->getAnswer()->contains($field)) {
            $this->getAnswer()->removeElement($field);
        }

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\CustomForm",
     *           inversedBy="customFormAnswers")
     * @JoinColumn(name="custom_form_id", referencedColumnName="id")
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
     * @todo Move this method to a CustomFormAnswerViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getIp().
            " — Sumitted : ".($this->getSummittedTime()).PHP_EOL;
    }
    /**
     * @todo Move this method to a CustomFormAnswerViewer
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
