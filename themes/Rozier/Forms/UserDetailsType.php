<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file UserDetailsType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\ValidFacebookName;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 *
 */
class UserDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'firstName',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'lastName',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255
                    ])
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => 'phone',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 20
                    ])
                ]
            ])
            ->add('facebookName', TextType::class, [
                'label' => 'facebookName',
                'required' => false,
                'constraints' => [
                    new ValidFacebookName(),
                ],
            ])
            ->add('company', TextType::class, [
                'label' => 'company',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255
                    ])
                ]
            ])
            ->add('job', TextType::class, [
                'label' => 'job',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255
                    ])
                ]
            ])
            ->add('birthday', DateType::class, [
                'label' => 'birthday',
                'placeholder' => [
                    'year' => 'year',
                    'month' => 'month',
                    'day' => 'day'
                ],
                'required' => false,
                'years' => range(1920, date('Y') - 6),
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'rz-datetime-field',
                ],
            ])
            ->add('pictureUrl', TextType::class, [
                'label' => 'pictureUrl',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255
                    ])
                ]
            ])
            ->add('locale', ChoiceType::class, [
                'label' => 'user.backoffice.language',
                'required' => false,
                'choices' => [
                    'English' => 'en',
                    'Español' => 'es',
                    'Français' => 'fr',
                    'Русский язык' => 'ru',
                    'Türkçe' => 'tr',
                    'Italiano' => 'it',
                    'српска ћирилица' => 'sr_Cyrl',
                ],
                'choices_as_values' => true,
                'placeholder' => 'use.website.default_language'
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'user';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
            'data_class' => User::class,
            'attr' => [
                'class' => 'uk-form user-form',
            ],
        ]);
    }
}
