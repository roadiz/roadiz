<?php
declare(strict_types=1);

namespace Themes\Install\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class DatabaseType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('driver', ChoiceType::class, [
                'choices' => [
                    'pdo_mysql' => 'pdo_mysql',
                    'pdo_pgsql' => 'pdo_pgsql',
                    'pdo_sqlite' => 'pdo_sqlite',
                ],
                'label' => 'driver',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
                'attr' => [
                    "id" => "choice",
                ],
            ])
            ->add('host', TextType::class, [
                "required" => false,
                'label' => 'host',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "host",
                ],
            ])
            ->add('port', IntegerType::class, [
                "required" => false,
                'label' => 'port',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "port",
                ],
            ])
            ->add('unix_socket', TextType::class, [
                "required" => false,
                'label' => 'unix_socket',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "unix_socket",
                ],
            ])
            ->add('path', TextType::class, [
                "required" => false,
                'label' => 'path',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "path",
                ],
            ])
            ->add('user', TextType::class, [
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "user",
                ],
                'label' => 'username',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
            ->add('password', PasswordType::class, [
                "required" => false,
                'label' => 'password',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => 'password',
                ],
            ])
            ->add('dbname', TextType::class, [
                "required" => false,
                'label' => 'dbname',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => 'dbname',
                ],
            ])
        ;
    }
}
