<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file FontVariantsType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Entities\Font;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Font variants selector form field type.
 */
class FontVariantsType extends AbstractType
{
    /**
     * {@inheritdoc}
     * @param OptionsResolverInterface $resolver [description]
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = array(
            Font::REGULAR      => 'regular',
            Font::BOLD         => 'bold',
            Font::ITALIC       => 'italic',
            Font::BOLD_ITALIC  => 'bold italic',
            Font::LIGHT        => 'light',
            Font::LIGHT_ITALIC => 'light italic',
        );

        $resolver->setDefaults(array(
            'choices' => $choices
        ));
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'fontVariants';
    }
}