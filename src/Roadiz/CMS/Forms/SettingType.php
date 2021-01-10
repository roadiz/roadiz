<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\AbstractDocumentFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

class SettingType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $groups = $options['entityManager']->getRepository(SettingGroup::class)->findAll();
        $choices = [];
        /** @var SettingGroup $group */
        foreach ($groups as $group) {
            $choices[$group->getName()] = $group->getId();
        }

        if ($options['shortEdit'] === false) {
            $builder
                ->add('name', TextType::class, [
                    'label' => 'name',
                    'constraints' => [
                        new NotNull(),
                        new NotBlank(),
                    ],
                ])
                ->add('description', MarkdownType::class, [
                    'label' => 'description',
                    'required' => false,
                ])
                ->add('visible', CheckboxType::class, [
                    'label' => 'visible',
                    'required' => false,
                ])
                ->add('encrypted', CheckboxType::class, [
                    'label' => 'encrypted',
                    'required' => false,
                ])
                ->add('type', ChoiceType::class, [
                    'label' => 'type',
                    'required' => true,
                    'choices' => array_flip(Setting::$typeToHuman),
                ])
                ->add('settingGroup', ChoiceType::class, [
                    'label' => 'setting.group',
                    'choices' => $choices,
                    'required' => false,
                    'placeholder' => '---------',
                ])
                ->add('defaultValues', TextType::class, [
                    'label' => 'defaultValues',
                    'attr' => [
                        'placeholder' => 'enter_values_comma_separated',
                    ],
                    'required' => false,
                ])
            ;

            $builder->get('settingGroup')->addModelTransformer(new CallbackTransformer(
                function (SettingGroup $settingGroup = null) {
                    if (null !== $settingGroup) {
                        // transform the array to a string
                        return $settingGroup->getId();
                    }
                    return null;
                },
                function ($id) use ($options) {
                    if (null !== $id) {
                        $group = $options['entityManager']->find(SettingGroup::class, $id);
                        return $group;
                    }
                    return null;
                }
            ));
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            /** @var Setting|null $setting */
            $setting = $event->getData();
            $form = $event->getForm();

            if (null !== $setting && $setting instanceof Setting) {
                if ($setting->getType() === AbstractField::DOCUMENTS_T) {
                    $form->add(
                        'value',
                        SettingDocumentType::class,
                        [
                            'label' => (!$options['shortEdit']) ? 'value' : false,
                            'entityManager' => $options['entityManager'],
                            'documentFactory' => $options['documentFactory'],
                            'assetPackages' => $options['assetPackages'],
                            'required' => false,
                        ]
                    );
                } else {
                    $form->add(
                        'value',
                        Setting::$typeToForm[$setting->getType()],
                        $this->getFormOptionsForSetting($setting, $options['shortEdit'])
                    );
                }
            } else {
                $form->add('value', TextType::class, [
                    'label' => 'value',
                    'required' => false,
                ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Setting::class);
        $resolver->setDefault('shortEdit', false);
        $resolver->setRequired([
            'entityManager',
            'documentFactory',
            'assetPackages',
        ]);
        $resolver->setDefault('constraints', [
            new UniqueEntity([
                'fields' => ['name'],
            ])
        ]);

        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
        $resolver->setAllowedTypes('documentFactory', [AbstractDocumentFactory::class]);
        $resolver->setAllowedTypes('assetPackages', [Packages::class]);
        $resolver->setAllowedTypes('shortEdit', ['boolean']);
    }

    protected function getFormOptionsForSetting(Setting $setting, $shortEdit = false)
    {
        $label = (!$shortEdit) ? 'value' : false;

        switch ($setting->getType()) {
            case AbstractField::ENUM_T:
            case AbstractField::MULTIPLE_T:
                $values = explode(',', $setting->getDefaultValues() ?? '');
                $values = array_map(function ($item) {
                    return trim($item);
                }, $values);
                return [
                    'label' => $label,
                    'placeholder' => 'choose.value',
                    'required' => false,
                    'choices' => array_combine($values, $values),
                    'multiple' => $setting->getType() === AbstractField::MULTIPLE_T ? true : false,
                ];
            case AbstractField::EMAIL_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Email(),
                    ]
                ];
            case AbstractField::DATETIME_T:
                return [
                    'placeholder' => [
                        'hour' => 'hour',
                        'minute' => 'minute',
                    ],
                    'date_widget' => 'single_text',
                    'date_format' => 'yyyy-MM-dd',
                    'attr' => [
                        'class' => 'rz-datetime-field',
                    ],
                    'label' => $label,
                    'years' => range((int) date('Y') - 10, (int) date('Y') + 10),
                    'required' => false,
                ];
            case AbstractField::INTEGER_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('integer'),
                    ],
                ];
            case AbstractField::DECIMAL_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'constraints' => [
                        new Type('double'),
                    ],
                ];
            default:
                return [
                    'label' => $label,
                    'required' => false,
                ];
        }
    }
}
