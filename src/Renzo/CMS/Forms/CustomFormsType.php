<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file CustomFormsType.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use RZ\Renzo\Core\Entities\CustomFormField;

class CustomFormsType extends AbstractType
{
    private $customForm;

    public function __construct($customForm) {
        $this->customForm = $customForm;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->customForm->getFields();

        foreach ($fields as $field) {
                $option = array("label" => $field->getLabel());
                $type = null;
                if ($field->isRequire()) {
                    $option['constraints'] = array(
                        new NotBlank()
                    );
                } else {
                    $option['required'] = false;
                }
                if (CustomFormField::$typeToForm[$field->getType()] == "enumeration") {
                    $choices = explode(',', $field->getDefaultValues());
                    $choices = array_combine(array_values($choices), array_values($choices));
                    $type = "choice";
                    if (count($choices) < 4) {
                        $option["expanded"] = true;
                    }
                    $option["choices"] = $choices;
                } elseif (CustomFormField::$typeToForm[$field->getType()] == "multiple_enumeration") {
                    $choices = explode(',', $field->getDefaultValues());
                    $choices = array_combine(array_values($choices), array_values($choices));
                    $type = "choice";
                    $option["choices"] = $choices;
                    $option["multiple"] = true;
                    $option["expanded"] = true;
                } else {
                    $type = CustomFormField::$typeToForm[$field->getType()];
                }
                $builder->add($field->getName(), $type, $option);
            }
    }

    public function getName()
    {
        return 'customForms';
    }
}