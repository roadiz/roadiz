<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeValueType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('attribute', AttributeChoiceType::class, [
            'label' => 'attribute_values.form.attribute',
            'entityManager' => $options['entityManager'],
            'translation' => $options['translation'],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', [EntityManagerInterface::class]);
        $resolver->setRequired('translation');
        $resolver->setAllowedTypes('translation', [Translation::class]);
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'attribute_value';
    }
}
