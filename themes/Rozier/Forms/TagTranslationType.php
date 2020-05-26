<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueTagName;
use RZ\Roadiz\CMS\Forms\MarkdownType;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\CMS\Forms\TagTranslationDocumentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * TagTranslationType.
 */
class TagTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
                'label' => 'name',
                'constraints' => [
                    new NotBlank(),
                    new UniqueTagName([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['tagName'],
                    ]),
                    new Length([
                        'max' => 255,
                    ])
                ],
            ])
            ->add('description', MarkdownType::class, [
                'label' => 'description',
                'required' => false,
            ])
            ->add('tagTranslationDocuments', TagTranslationDocumentType::class, [
                'label' => 'documents',
                'required' => false,
                'tagTranslation' => $builder->getForm()->getData(),
                'entityManager' => $options['em'],
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'tag_translation';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'tagName' => '',
            'data_class' => TagTranslation::class,
            'attr' => [
                'class' => 'uk-form tag-translation-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', ObjectManager::class);
        $resolver->setAllowedTypes('tagName', 'string');
    }
}
