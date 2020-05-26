<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\MarkdownType;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('referer', HiddenType::class, [
                'data' => $options['referer'],
                'mapped' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'name',
                'required' => false,
            ])
            ->add('description', MarkdownType::class, [
                'label' => 'description',
                'required' => false,
            ])
            ->add('copyright', TextType::class, [
                'label' => 'copyright',
                'required' => false,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DocumentTranslation::class
        ]);

        $resolver->setRequired('referer');
        $resolver->setAllowedTypes('referer', ['null', 'string']);
    }
}
