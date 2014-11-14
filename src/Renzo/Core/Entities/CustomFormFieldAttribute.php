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
 * @file CustomFormFieldAttribute.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Renzo\Core\Entities;

use RZ\Renzo\Core\AbstractEntities\PersistableInterface;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;
use RZ\Renzo\Core\Utils\StringHandler;

/**
 * CustomFormField entities are used to create CustomForms with
 * custom data structure.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="custom_form_field_attributes")
 * @HasLifecycleCallbacks
 */
class CustomFormFieldAttribute extends AbstractEntity
{

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\CustomFormAnswer", inversedBy="answerField")
     * @JoinColumn(name="custom_form_answer_id", referencedColumnName="id", onDelete="CASCADE")
     */

    private $customFormAnswer;

    public function setCustomFormAnswer($customFormAnswer)
    {
        $this->customFormAnswer = $customFormAnswer;
        return $this;
    }

    public function getCustomFormAnswer()
    {
        return $this->customFormAnswer;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\CustomFormField", inversedBy="customFormFieldAttribute")
     * @JoinColumn(name="custom_form_field_id", referencedColumnName="id")
     */
    private $customFormField;

    public function setCustomFormField($customFormField)
    {
        $this->customFormField = $customFormField;
        return $this;
    }

    public function getCustomFormField()
    {
        return $this->customFormField;
    }

    /**
     * @Column(type="string", nullable=true)
     */
    private $value;

    /**
     * @return string $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

}