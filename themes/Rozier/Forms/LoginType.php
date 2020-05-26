<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('_username', TextType::class, [
            'label' => 'username',
            'attr' => [
                'autocomplete' => 'username'
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('_password', PasswordType::class, [
            'label' => 'password',
            'attr' => [
                'autocomplete' => 'current-password'
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('_remember_me', CheckboxType::class, [
            'label' => 'keep_me_logged_in',
            'required' => false,
            'attr' => [
                'checked' => true
            ],
        ]);

        if ($options['requestStack']->getMasterRequest()->query->has('_home')) {
            $builder->add('_target_path', HiddenType::class, [
                'data' => $options['urlGenerator']->generate('adminHomePage')
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('action', function (Options $options) {
            return $options['urlGenerator']->generate('loginCheckPage');
        });
        $resolver->setRequired('urlGenerator');
        $resolver->setRequired('requestStack');
        $resolver->setAllowedTypes('urlGenerator', [UrlGeneratorInterface::class]);
        $resolver->setAllowedTypes('requestStack', [RequestStack::class]);
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        /*
         * No prefix for firewall to catch username and password from request.
         */
        return null;
    }
}
