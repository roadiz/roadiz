<?php

declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\ValidFacebookName;
use RZ\Roadiz\CMS\Forms\NodesType;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserSecurityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('enabled', CheckboxType::class, [
                'label' => 'user.enabled',
                'required' => false,
            ])
            ->add('locked', CheckboxType::class, [
                'label' => 'user.locked',
                'required' => false,
            ])
            ->add('expiresAt', DateTimeType::class, [
                'label' => 'user.expiresAt',
                'required' => false,
                'years' => range(date('Y'), date('Y') + 2),
                'date_widget' => 'single_text',
                'date_format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'rz-datetime-field',
                ],
                'placeholder' => [
                    'hour' => 'hour',
                    'minute' => 'minute',
                ],
            ])
            ->add('expired', CheckboxType::class, [
                'label' => 'user.force.expired',
                'required' => false,
            ])
            ->add('credentialsExpiresAt', DateTimeType::class, [
                'label' => 'user.credentialsExpiresAt',
                'required' => false,
                'years' => range(date('Y'), date('Y') + 2),
                'date_widget' => 'single_text',
                'date_format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'rz-datetime-field',
                ],
                'placeholder' => [
                    'hour' => 'hour',
                    'minute' => 'minute',
                ],
            ])
            ->add('credentialsExpired', CheckboxType::class, [
                'label' => 'user.force.credentialsExpired',
                'required' => false,
            ]);

        if ($options['canChroot'] === true) {
            $builder->add('chroot', NodesType::class, [
                'label' => 'chroot',
                'required' => false,
                'multiple' => false,
                'entityManager' => $options['entityManager'],
            ]);
        }
    }

    public function getBlockPrefix()
    {
        return 'user_security';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => User::class,
            'canChroot' => false,
            'attr' => [
                'class' => 'uk-form user-form',
            ],
        ]);

        $resolver->setRequired([
            'entityManager'
        ]);
        $resolver->setAllowedTypes('canChroot', ['bool']);
        $resolver->setAllowedTypes('entityManager', [EntityManagerInterface::class]);
    }
}
