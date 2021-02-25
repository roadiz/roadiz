<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\ValidAccountConfirmationToken;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginResetForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', HiddenType::class, [
            'required' => true,
            'data' => $options['token'],
            'label' => false,
            'constraints' => [
                new ValidAccountConfirmationToken([
                    'ttl' => $options['confirmationTtl'],
                    'message' => 'confirmation.token.is.invalid',
                    'expiredMessage' => 'confirmation.token.has.expired',
                ]),
            ],
        ])
        ->add('plainPassword', CreatePasswordType::class, [
            'required' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'login_reset';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'token',
            'confirmationTtl',
        ]);

        $resolver->setAllowedTypes('token', ['string']);
        $resolver->setAllowedTypes('confirmationTtl', ['int']);
    }
}
