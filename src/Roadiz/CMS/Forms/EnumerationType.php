<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Group selector form field type.
 */
class EnumerationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'strict' => true,
            'multiple' => false,
            'placeholder' => 'choose.value',
        ]);

        $resolver->setRequired(['nodeTypeField']);
        $resolver->setAllowedTypes('nodeTypeField', [NodeTypeField::class]);

        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            $values = explode(',', $options['nodeTypeField']->getDefaultValues() ?? '');

            foreach ($values as $value) {
                $value = trim($value);
                $choices[$value] = $value;
            }
            return $choices;
        });

        $resolver->setNormalizer('placeholder', function (Options $options, $placeholder) {
            if ('' !== $options['nodeTypeField']->getPlaceholder()) {
                $placeholder = $options['nodeTypeField']->getPlaceholder();
            }
            return $placeholder;
        });

        $resolver->setNormalizer('expanded', function (Options $options, $expanded) {
            return $options['nodeTypeField']->isExpanded();
        });
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'enumeration';
    }
}
