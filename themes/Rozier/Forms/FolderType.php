<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @package Themes\Rozier\Forms
 */
class FolderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('folderName', TextType::class, [
            'label' => 'folder.name',
            'constraints' => [
                new NotNull(),
                new NotBlank(),
                new Length([
                    'max' => 255,
                ])
            ],
        ])
        ->add('visible', CheckboxType::class, [
            'label' => 'visible',
            'required' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'folder';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'name' => '',
            'data_class' => Folder::class,
            'attr' => [
                'class' => 'uk-form folder-form',
            ],
            'constraints' => [
                new UniqueEntity([
                    'fields' => ['folderName']
                ])
            ]
        ]);
    }
}
