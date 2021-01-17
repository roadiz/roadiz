<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use RZ\Roadiz\Core\Entities\FolderTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @package Themes\Rozier\Forms
 */
class FolderTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'name',
            'constraints' => [
                new Length([
                    'max' => 255,
                ])
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'folder_translation';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => FolderTranslation::class,
            'attr' => [
                'class' => 'uk-form folder-form',
            ],
        ]);
    }
}
