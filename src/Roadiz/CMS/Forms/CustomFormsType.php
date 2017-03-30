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

use RZ\Roadiz\CMS\Forms\Constraints\Recaptcha;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CustomFormsType
 * @package RZ\Roadiz\CMS\Forms
 */
class CustomFormsType extends AbstractType
{
    protected $customForm;
    protected $forceExpanded;

    /**
     * @param \RZ\Roadiz\Core\Entities\CustomForm $customForm
     * @param boolean $forceExpanded
     */
    public function __construct(CustomForm $customForm, $forceExpanded = false)
    {
        $this->customForm = $customForm;
        $this->forceExpanded = (boolean) $forceExpanded;
    }

    /**
     * @param  FormBuilderInterface $builder
     * @param  array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->customForm->getFields();

        /** @var CustomFormField $field */
        foreach ($fields as $field) {
            $option = [
                "label" => $field->getLabel(),
                'attr' => [
                    'data-description' => $field->getDescription(),
                    'data-desc' => $field->getDescription(),
                ],
            ];

            if ($field->isRequired()) {
                $option['required'] = true;
                $option['constraints'] = [
                    new NotBlank([
                        'message' => 'you.need.to.fill.this.required.field'
                    ])
                ];
            } else {
                $option['required'] = false;
            }

            $type = 'text';

            if ($field->getType() == AbstractField::ENUM_T) {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_map('trim', $choices);
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option["expanded"] = false;
                if (count($choices) < 4 || $this->forceExpanded) {
                    $option["expanded"] = true;
                }
                if ($field->isRequired() === false) {
                    $option['placeholder'] = 'none';
                }
                $option["choices"] = $choices;
            } elseif ($field->getType() == AbstractField::MULTIPLE_T) {
                $choices = explode(',', $field->getDefaultValues());
                $choices = array_map('trim', $choices);
                $choices = array_combine(array_values($choices), array_values($choices));
                $type = "choice";
                $option["choices"] = $choices;
                $option["multiple"] = true;

                $option["expanded"] = false;
                if (count($choices) < 4 || $this->forceExpanded) {
                    $option["expanded"] = true;
                }

                if ($field->isRequired() === false) {
                    $option['placeholder'] = 'none';
                }
            } elseif ($field->getType() == AbstractField::DOCUMENTS_T) {
                $option['required'] = false;
            } else {
                $type = CustomFormField::$typeToForm[$field->getType()];
            }

            if ($field->getType() === CustomFormField::MARKDOWN_T) {
                $type = new MarkdownType();
            } elseif ($field->getType() === CustomFormField::DOCUMENTS_T) {
                $type = "file";
            }

            $builder->add($field->getName(), $type, $option);
        }

        /*
         * Add Google Recaptcha if setting optionnal options.
         */
        if (!empty($options['recaptcha_public_key']) &&
            !empty($options['recaptcha_private_key']) &&
            !empty($options['request'])) {
            $verifyUrl = !empty($options['recaptcha_verifyurl']) ?
                $options['recaptcha_verifyurl'] :
                'https://www.google.com/recaptcha/api/siteverify';

            $builder->add('recaptcha', new RecaptchaType(), [
                'label' => false,
                'configs' => [
                    'publicKey' => $options['recaptcha_public_key'],
                ],
                'constraints' => [
                    new Recaptcha($options['request'], [
                        'privateKey' => $options['recaptcha_private_key'],
                        'verifyUrl' => $verifyUrl,
                    ]),
                ],
            ]);
        }
    }

    /**
     * @param OptionsResolver $optionsResolver
     */
    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'recaptcha_public_key' => null,
            'recaptcha_private_key' => null,
            'recaptcha_verifyurl' => null,
            'request' => null,
        ]);

        $optionsResolver->setAllowedTypes('request', ['Symfony\Component\HttpFoundation\Request', 'null']);
        $optionsResolver->setAllowedTypes('recaptcha_public_key', ['string', 'null', 'boolean']);
        $optionsResolver->setAllowedTypes('recaptcha_private_key', ['string', 'null', 'boolean']);
        $optionsResolver->setAllowedTypes('recaptcha_verifyurl', ['string', 'null', 'boolean']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'custom_form_'.$this->customForm->getId();
    }
}
