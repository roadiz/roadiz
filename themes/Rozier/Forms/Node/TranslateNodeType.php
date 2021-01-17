<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms\Node;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\DataTransformer\TranslationTransformer;
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
        $translations = $this->entityManager->getRepository(Translation::class)
                           ->findUnavailableTranslationsForNode($options['node']);
        $choices = [];

        /** @var Translation $translation */
        foreach ($translations as $translation) {
            $choices[$translation->getName()] = $translation->getId();
        }

        $builder->add('translation', ChoiceType::class, [
            'label' => 'translation',
            'choices' => $choices,
            'required' => true,
            'multiple' => false,
        ])
        ->add('translate_offspring', CheckboxType::class, [
            'label' => 'translate_offspring',
            'required' => false,
        ]);

        $builder->get('translation')
            ->addModelTransformer(new TranslationTransformer($this->entityManager));
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
