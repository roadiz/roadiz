<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEmail;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueUsername;
use RZ\Roadiz\CMS\Forms\CreatePasswordType;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class UserType
 *
 * @package Themes\Rozier\Forms
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class, [
                'label' => 'email',
                'constraints' => [
                    new NotNull(),
                    new Email(),
                    new NotBlank(),
                    new UniqueEmail([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['email'],
                    ]),
                    new UniqueUsername([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['username'],
                    ]),
                    new Length([
                        'max' => 200,
                    ])
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'username',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                    new UniqueUsername([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['username'],
                    ]),
                    new UniqueEmail([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['email'],
                    ]),
                    new Length([
                        'max' => 200
                    ])
                ],
            ])
            ->add('plainPassword', CreatePasswordType::class, [
                'invalid_message' => 'password.must.match',
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
            'email' => '',
            'username' => '',
            'data_class' => User::class,
            'attr' => [
                'class' => 'uk-form user-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', EntityManagerInterface::class);
        $resolver->setAllowedTypes('email', 'string');
        $resolver->setAllowedTypes('username', 'string');
    }
}
