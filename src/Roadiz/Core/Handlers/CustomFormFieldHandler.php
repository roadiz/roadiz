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
 * @file CustomFormFieldHandler.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Persistence\ObjectManager;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\CustomFormField;

/**
 * Handle operations with customForms fields entities.
 */
class CustomFormFieldHandler extends AbstractHandler
{
    /** @var null|CustomFormField  */
    private $customFormField = null;

    /** @var \Pimple\Container  */
    private $container;

    /**
     * @return CustomFormField
     */
    public function getCustomFormField()
    {
        return $this->customFormField;
    }
    /**
     * @param CustomFormField $customFormField
     *
     * @return $this
     */
    public function setCustomFormField(CustomFormField $customFormField)
    {
        $this->customFormField = $customFormField;
        return $this;
    }

    /**
     * Create a new custom-form-field handler with custom-form-field to handle.
     *
     * @param ObjectManager $entityManager
     * @param Container $container
     */
    public function __construct(ObjectManager $entityManager, Container $container)
    {
        parent::__construct($entityManager);
        $this->container = $container;
    }

    /**
     * Clean position for current customForm siblings.
     *
     * @param bool $setPositions
     * @return int Return the next position after the **last** customFormField
     */
    public function cleanPositions($setPositions = true)
    {
        if ($this->customFormField->getCustomForm() !== null) {
            /** @var CustomFormHandler $customFormHandler */
            $customFormHandler = $this->container['custom_form.handler'];
            $customFormHandler->setCustomForm($this->customFormField->getCustomForm());
            return $customFormHandler->cleanFieldsPositions($setPositions);
        }

        return 1;
    }
}
