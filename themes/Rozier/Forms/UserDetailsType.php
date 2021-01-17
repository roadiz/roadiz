<?php
declare(strict_types=1);

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
use Themes\Rozier\RozierApp;

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
                'years' => range(1920, ((int) date('Y')) - 6),
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
                'choices' => RozierApp::$backendLanguages,
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
