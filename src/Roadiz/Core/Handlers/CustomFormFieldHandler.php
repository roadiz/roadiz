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
 * @file CustomFormFieldHandler.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\CustomFormField;

/**
 * Handle operations with customForms fields entities.
 */
class CustomFormFieldHandler
{

    private $customFormField = null;
    /**
     * @return CustomFormField
     */
    public function getCustomFormField()
    {
        return $this->customFormField;
    }
    /**
     * @param CustomFormeField $customFormField
     *
     * @return $this
     */
    public function setCustomFormField($customFormField)
    {
        $this->customFormField = $customFormField;

        return $this;
    }

    protected function getDecimalPrecision()
    {
        if ($this->customFormField->getType() == CustomFormField::DECIMAL_T) {
            return 'precision=10, scale=3, ';
        } else {
            return '';
        }
    }

    /**
     * Create a new custom-form-field handler with custom-form-field to handle.
     *
     * @param CustomFormField $field
     */
    public function __construct(CustomFormField $field)
    {
        $this->customFormField = $field;
    }

    /**
     * Clean position for current customForm siblings.
     *
     * @return int Return the next position after the **last** customFormField
     */
    public function cleanPositions()
    {
        if ($this->customFormField->getCustomForm() !== null) {
            return $this->customFormField->getCustomForm()->getHandler()->cleanFieldsPositions();
        }
    }
}
