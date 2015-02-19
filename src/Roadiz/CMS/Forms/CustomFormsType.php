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
 * @file CustomFormsType.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use RZ\Roadiz\Core\Entities\CustomFormField;

class CustomFormsType extends AbstractType
{
    private $customForm;

    public function __construct($customForm)
    {
        $this->customForm = $customForm;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->customForm->getFields();

        foreach ($fields as $field) {
            $option = ["label" => $field->getLabel()];

            if ($field->isRequired()) {
                $option['required'] = true;
                $option['constraints'] = [
                    new NotBlank()
                ];
            } else {
                $option['required'] = false;
            }
            if ($field->getType() == AbstractField::ENUM_T) {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
                if ($field->isRequired() === false) {
                    $option['empty_value'] = 'none';
                }
                $option["choices"] = $choices;
            } elseif ($field->getType() == AbstractField::MULTIPLE_T) {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option["choices"] = $choices;
                $option["multiple"] = true;
                $option["expanded"] = false;
                if (count($choices) < 4) {
                    $option["expanded"] = true;
                }
                if ($field->isRequired() === false) {
                    $option['empty_value'] = 'none';
                }
            } elseif ($field->getType() == AbstractField::DOCUMENTS_T) {
                $option['required'] = false;
            } else {
                $type = CustomFormField::$typeToForm[$field->getType()];
            }

            if ($field->getType() === CustomFormField::MARKDOWN_T) {
                $type = new \RZ\Roadiz\CMS\Forms\MarkdownType();
            } elseif ($field->getType() === CustomFormField::DOCUMENTS_T) {
                $type = "file";
            }

            $builder->add($field->getName(), $type, $option);
        }
    }

    public function getName()
    {
        return 'custom_form_'.$this->customForm->getId();
    }
}
