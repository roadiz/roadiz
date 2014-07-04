<?php 


namespace RZ\Renzo\CMS\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
* 
*/
class MarkdownType extends AbstractType
{
	
    public function getParent()
    {
        return 'textarea';
    }

    public function getName()
    {
        return 'markdown';
    }
}