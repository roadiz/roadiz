<?php
declare(strict_types=1);

namespace Themes\Install\Forms;

use RZ\Roadiz\CMS\Forms\SeparatorType;
use RZ\Roadiz\CMS\Forms\ThemesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

class SiteInformationType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $timeZoneList = include dirname(__DIR__) . '/Resources/import/timezones.php';

        $builder->add('site_name', TextType::class, [
                'required' => true,
                'label' => 'site_name',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
            ->add('email_sender', EmailType::class, [
                'required' => true,
                'label' => 'email_sender',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
            ->add('email_sender_name', TextType::class, [
                'required' => true,
                'label' => 'email_sender_name',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
            ->add('seo_description', TextType::class, [
                'required' => false,
                'label' => 'meta_description',
            ])
        ;

        if (count($options['themes_config']) > 0) {
            $builder->add('separator_1', SeparatorType::class, [
                    'label' => 'themes.frontend.description',
                ])
                ->add('install_theme', CheckboxType::class, [
                    'required' => false,
                    'label' => 'install_theme',
                    'data' => true,
                ])
                ->add('className', ThemesType::class, [
                    'themes_config' => $options['themes_config'],
                    'label' => 'theme.selector',
                    'required' => true,
                    'constraints' => [
                        new NotNull(),
                        new Type('string'),
                    ],
                ])
            ;
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('themes_config');
        $resolver->setAllowedTypes('themes_config', 'array');
    }
}
