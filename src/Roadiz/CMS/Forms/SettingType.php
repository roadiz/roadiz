<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file SettingType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\ORM\EntityManager;
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
                        new NotBlank(),
                    ],
                ])
                ->add('visible', CheckboxType::class, [
                    'label' => 'visible',
                    'required' => false,
                ])
                ->add('type', ChoiceType::class, [
                    'label' => 'type',
                    'required' => true,
                    'choices' => array_flip(Setting::$typeToHuman),
                    'choices_as_values' => true,
                ])
                ->add('settingGroup', ChoiceType::class, [
                    'label' => 'setting.group',
                    'choices_as_values' => true,
                    'choices' => $choices,
                    'placeholder' => '---------',
                ])
                ->add('defaultValues', TextType::class, [
                    'label' => 'defaultValues',
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
                    $group = $options['entityManager']->find(SettingGroup::class, $id);
                    return $group;
                }
            ));
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            /** @var Setting $setting */
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
                $values = explode(',', $setting->getDefaultValues());
                $values = array_map(function ($item) {
                    return trim($item);
                }, $values);
                return [
                    'label' => $label,
                    'placeholder' => 'choose.value',
                    'required' => false,
                    'choices_as_values' => true,
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
                    'label' => $label,
                    'years' => range(date('Y') - 10, date('Y') + 10),
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
            case AbstractField::COLOUR_T:
                return [
                    'label' => $label,
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker-input',
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
