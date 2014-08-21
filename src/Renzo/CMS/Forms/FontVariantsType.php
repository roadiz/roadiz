<?php 


namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Entities\Font;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class FontVariantsType extends AbstractType
{
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

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'fontVariants';
    }
}