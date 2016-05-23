<?php
/**
 * Copyright (c) Rezo Zero 2016.
 *
 * prison-insider
 *
 * Created on 05/05/16 15:32
 *
 * @author ambroisemaupate
 * @file FolderType.php
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueFolderName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FolderType
 * @package Themes\Rozier\Forms
 */
class FolderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('folderName', 'text', [
            'label' => 'folder.name',
            'constraints' => [
                new NotBlank(),
                new UniqueFolderName([
                    'entityManager' => $options['em'],
                    'currentValue' => $options['name'],
                ]),
            ],
        ])
        ->add('visible', 'checkbox', [
            'label' => 'visible',
            'required' => false,
        ]);
    }

    public function getName()
    {
        return 'folder';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'name' => '',
            'data_class' => 'RZ\Roadiz\Core\Entities\Folder',
            'attr' => [
                'class' => 'uk-form folder-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);
        $resolver->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager');
        $resolver->setAllowedTypes('name', 'string');
    }
}
