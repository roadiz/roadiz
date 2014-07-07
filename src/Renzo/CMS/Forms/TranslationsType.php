<?php 


namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class TranslationsType extends AbstractType
{
	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {	
    	$translations = Kernel::getInstance()->em()
    		->getRepository('RZ\Renzo\Core\Entities\Translation')
    		->findAll();

    	$choices = array();
    	foreach ($translations as $translation) {
    		$choices[$translation->getId()] = $translation->getName();
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
        return 'translations';
    }
}