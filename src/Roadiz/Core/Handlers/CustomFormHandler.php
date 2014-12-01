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
 * @file CustomFormHandler.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\CustomForm;

/**
 * Handle operations with node-type entities.
 */
class CustomFormHandler
{
    private $customForm = null;

    /**
     * @return RZ\Roadiz\Core\Entities\CustomForm
     */
    public function getCustomForm()
    {
        return $this->customForm;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return $this
     */
    public function setCustomForm($customForm)
    {
        $this->customForm = $customForm;

        return $this;
    }

    /**
     * Create a new node-type handler with node-type to handle.
     *
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     */
    public function __construct(CustomForm $customForm)
    {
        $this->customForm = $customForm;
    }

    /**
     * Reset current node-type fields positions.
     *
     * @return int Return the next position after the **last** field
     */
    public function cleanFieldsPositions()
    {
        $fields = $this->customForm->getFields();
        $i = 1;
        foreach ($fields as $field) {
            $field->setPosition($i);
            $i++;
        }

        Kernel::getService('em')->flush();

        return $i;
    }
}
