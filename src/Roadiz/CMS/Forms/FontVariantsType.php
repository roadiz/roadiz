<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Entities\Font;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Font variants selector form field type.
 */
class FontVariantsType extends AbstractType
{
    /**
     * {@inheritdoc}
     * @param OptionsResolver $resolver [description]
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => array_flip(Font::$variantToHuman),
        ]);
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
        return 'font_variants';
    }
}
