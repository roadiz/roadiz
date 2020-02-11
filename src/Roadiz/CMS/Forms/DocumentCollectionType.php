<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\DataTransformer\DocumentCollectionTransformer;
use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new DocumentCollectionTransformer($options['entityManager'], true));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'class' => Document::class,
            'multiple' => true,
            'property' => 'id',
        ]);
        $resolver->setRequired([
            'entityManager'
        ]);
        $resolver->setAllowedTypes('entityManager', EntityManagerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'documents';
    }
}
