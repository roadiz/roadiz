<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Rollerworks\Component\PasswordStrength\Validator\Constraints\Blacklist;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordStrength;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreatePasswordType extends RepeatedType
{
    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'type' => PasswordType::class,
            'invalid_message' => 'password.must.match',
            'options' => [
                'constraints' => [
                    new Blacklist(),
                    new PasswordStrength([
                        'minLength' => 8,
                        'minStrength' => 3,
                        'message' => 'password_should_contains_at_least_one_capital_one_digit',
                        'tooShortMessage' => 'password_should_be_at_least_{{length}}_characters_long',
                    ])
                ]
            ],
            'first_options' => [
                'label' => 'choose.a.new.password',
            ],
            'second_options' => [
                'label' => 'passwordVerify',
            ],
            'required' => false,
            'error_mapping' => function (Options $options) {
                return ['.' => $options['first_name']];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'repeated';
    }
}
