<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueTranslationLocale;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueTranslationOverrideLocale;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * TranslationType.
 */
class TranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'name',
            'constraints' => [
                new NotBlank(),
                new Length([
                    'max' => 255,
                ])
            ],
        ])
        ->add('locale', ChoiceType::class, [
            'label' => 'locale',
            'required' => true,
            'choices' => array_flip(Translation::$availableLocales),
            'constraints' => [
                new UniqueTranslationLocale([
                    'entityManager' => $options['em'],
                    'currentValue' => $options['locale'],
                ]),
            ],
        ])
        ->add('available', CheckboxType::class, [
            'label' => 'available',
            'required' => false,
        ])
        ->add('overrideLocale', TextType::class, [
            'label' => 'overrideLocale',
            'required' => false,
            'constraints' => [
                new UniqueTranslationOverrideLocale([
                    'entityManager' => $options['em'],
                    'currentValue' => $options['overrideLocale'],
                ]),
                new Length([
                    'max' => 7,
                ])
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'translation';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'locale' => '',
            'overrideLocale' => '',
            'data_class' => Translation::class,
            'attr' => [
                'class' => 'uk-form translation-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', ObjectManager::class);
        $resolver->setAllowedTypes('locale', ['string']);
        $resolver->setAllowedTypes('overrideLocale', ['string', 'null']);
    }
}
