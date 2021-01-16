<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\Recaptcha;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @package RZ\Roadiz\CMS\Forms
 */
class CustomFormsType extends AbstractType
{
    /**
     * @param  FormBuilderInterface $builder
     * @param  array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fieldsArray = $this->getFieldsByGroups($options);

        /** @var CustomFormField|array $field */
        foreach ($fieldsArray as $group => $field) {
            if ($field instanceof CustomFormField) {
                $this->addSingleField($builder, $field, $options);
            } elseif (is_array($field)) {
                $groupCanonical = StringHandler::slugify($group);
                $subBuilder = $builder->create($groupCanonical, FormType::class, [
                    'label' => $group,
                    'inherit_data' => true,
                    'attr' => [
                        'data-group-wrapper' => $groupCanonical,
                    ]
                ]);
                /** @var CustomFormField $subfield */
                foreach ($field as $subfield) {
                    $this->addSingleField($subBuilder, $subfield, $options);
                }
                $builder->add($subBuilder);
            }
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

            $builder->add('recaptcha', RecaptchaType::class, [
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
     * @param array $options
     * @return array
     */
    protected function getFieldsByGroups(array $options)
    {
        $fieldsArray = [];
        $fields = $options['customForm']->getFields();

        /** @var CustomFormField $field */
        foreach ($fields as $field) {
            if ($field->getGroupName() != '') {
                if (!isset($fieldsArray[$field->getGroupName()])) {
                    $fieldsArray[$field->getGroupName()] = [];
                }
                $fieldsArray[$field->getGroupName()][] = $field;
            } else {
                $fieldsArray[] = $field;
            }
        }

        return $fieldsArray;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param CustomFormField $field
     * @param array $formOptions
     * @return $this
     */
    protected function addSingleField(FormBuilderInterface $builder, CustomFormField $field, array $formOptions)
    {
        $builder->add(
            $field->getName(),
            $this->getTypeForField($field),
            $this->getOptionsForField($field, $formOptions)
        );
        return $this;
    }

    /**
     * @param CustomFormField $field
     * @return MarkdownType|string
     */
    protected function getTypeForField(CustomFormField $field)
    {
        switch ($field->getType()) {
            case AbstractField::ENUM_T:
            case AbstractField::MULTIPLE_T:
                return ChoiceType::class;
            case AbstractField::DOCUMENTS_T:
                return FileType::class;
            case AbstractField::MARKDOWN_T:
                return MarkdownType::class;
            default:
                return NodeSourceType::getFormTypeFromFieldType($field);
        }
    }

    /**
     * @param CustomFormField $field
     * @param array $formOptions
     * @return array
     */
    protected function getOptionsForField(CustomFormField $field, array $formOptions)
    {
        $option = [
            "label" => $field->getLabel(),
            'help' => $field->getDescription(),
            'attr' => [
                'data-group' => $field->getGroupName(),
            ],
        ];

        if ($field->getPlaceholder() !== '') {
            $option['attr']['placeholder'] = $field->getPlaceholder();
        }

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

        switch ($field->getType()) {
            case AbstractField::ENUM_T:
                if ($field->getPlaceholder() !== '') {
                    $option['placeholder'] = $field->getPlaceholder();
                }
                $option["choices"] = $this->getChoices($field);
                $option["expanded"] = $field->isExpanded();

                if ($formOptions['forceExpanded']) {
                    $option["expanded"] = true;
                }
                if ($field->isRequired() === false) {
                    $option['placeholder'] = 'none';
                }
                break;
            case AbstractField::MULTIPLE_T:
                if ($field->getPlaceholder() !== '') {
                    $option['placeholder'] = $field->getPlaceholder();
                }
                $option["choices"] = $this->getChoices($field);
                $option["multiple"] = true;
                $option["expanded"] = $field->isExpanded();

                if ($formOptions['forceExpanded']) {
                    $option["expanded"] = true;
                }
                if ($field->isRequired() === false) {
                    $option['placeholder'] = 'none';
                }
                break;
            case AbstractField::DOCUMENTS_T:
                $option['multiple'] = true;
                $option['mapped'] = false;
                $mimeTypes = [
                    'application/pdf',
                    'application/x-pdf',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                ];
                if (!empty($field->getDefaultValues())) {
                    $mimeTypes = explode(',', $field->getDefaultValues() ?? '');
                    $mimeTypes = array_map('trim', $mimeTypes);
                }
                $option['constraints'][] = new All([
                    'constraints' => [
                        new File([
                            'maxSize' => '10m',
                            'mimeTypes' => $mimeTypes
                        ])
                    ]
                ]);
                break;
            case AbstractField::COUNTRY_T:
                $option["expanded"] = $field->isExpanded();
                if ($field->getPlaceholder() !== '') {
                    $option['placeholder'] = $field->getPlaceholder();
                }
                if ($field->getDefaultValues() !== '') {
                    $countries = explode(',', $field->getDefaultValues() ?? '');
                    $countries = array_map('trim', $countries);
                    $option['preferred_choices'] = $countries;
                }
                break;
            case AbstractField::EMAIL_T:
                if (!isset($option['constraints'])) {
                    $option['constraints'] = [];
                }
                $option['constraints'][] = new Email();
                break;
            default:
                break;
        }
        return $option;
    }

    /**
     * @param CustomFormField $field
     * @return array
     */
    protected function getChoices(CustomFormField $field)
    {
        $choices = explode(',', $field->getDefaultValues() ?? '');
        $choices = array_map('trim', $choices);
        $choices = array_combine(array_values($choices), array_values($choices));

        return $choices;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'recaptcha_public_key' => null,
            'recaptcha_private_key' => null,
            'recaptcha_verifyurl' => null,
            'request' => null,
            'forceExpanded' => false,
        ]);

        $resolver->setRequired('customForm');

        $resolver->setAllowedTypes('customForm', [CustomForm::class]);
        $resolver->setAllowedTypes('forceExpanded', ['boolean']);
        $resolver->setAllowedTypes('request', [Request::class, 'null']);
        $resolver->setAllowedTypes('recaptcha_public_key', ['string', 'null', 'boolean']);
        $resolver->setAllowedTypes('recaptcha_private_key', ['string', 'null', 'boolean']);
        $resolver->setAllowedTypes('recaptcha_verifyurl', ['string', 'null', 'boolean']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'custom_form_public';
    }
}
