<?php
declare(strict_types=1);

namespace Themes\Install\Forms;

use RZ\Roadiz\CMS\Forms\CreatePasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'required' => true,
                'label' => 'username',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'email',
                'constraints' => [
                    new NotNull(),
                    new Email(),
                    new NotBlank(),
                ],
            ])
            ->add('password', CreatePasswordType::class, [
                'invalid_message' => 'password.must_match',
                'first_options' => ['label' => 'password'],
                'second_options' => ['label' => 'password.verify'],
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;
    }
}
