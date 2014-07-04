<?php 


namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
* 
*/
class TagsType extends AbstractType
{
	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {	
    	$tags = Kernel::getInstance()->em()
    		->getRepository('RZ\Renzo\Core\Entities\Tag')
    		->findAllWithDefaultTranslation();

    	$choices = array();
    	foreach ($tags as $tag) {
    		$choices[$tag->getId()] = $tag->getDefaultTranslatedTag()->getName();
    	}

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
        return 'tags';
    }
}