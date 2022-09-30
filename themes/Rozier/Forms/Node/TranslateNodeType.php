<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms\Node;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package Themes\Rozier\Forms\Node
 */
class TranslateNodeType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translations = $this->entityManager
            ->getRepository(Translation::class)
            ->findUnavailableTranslationsForNode($options['node']);
        $availableTranslations = $this->entityManager
            ->getRepository(Translation::class)
            ->findAvailableTranslationsForNode($options['node']);

        $builder
            ->add('sourceTranslation', ChoiceType::class, [
                'label' => 'source_translation',
                'help' => 'source_translation.help',
                'choices' => $availableTranslations,
                'required' => true,
                'multiple' => false,
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('translation', ChoiceType::class, [
                'label' => 'destination_translation',
                'choices' => $translations,
                'required' => true,
                'multiple' => false,
                'choice_value' => 'id',
                'choice_label' => 'name',
            ])
            ->add('translate_offspring', CheckboxType::class, [
                'label' => 'translate_offspring',
                'help' => 'translate_offspring.help',
                'required' => false,
            ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'translate_node';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'attr' => [
                'class' => 'uk-form node-translation-form',
            ],
        ]);

        $resolver->setRequired([
            'node',
        ]);

        $resolver->setAllowedTypes('node', Node::class);
    }
}
