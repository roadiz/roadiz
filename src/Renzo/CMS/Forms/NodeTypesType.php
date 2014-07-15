<?php 


namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
* 
*/
class NodeTypesType extends AbstractType
{
	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {	
    	$NodeTypes = Kernel::getInstance()->em()
    		->getRepository('RZ\Renzo\Core\Entities\NodeType')
    		->findAll();

    	$choices = array();
    	foreach ($NodeTypes as $NodeType) {
    		$choices[$NodeType->getId()] = $NodeType->getDisplayName();
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
        return 'NodeTypes';
    }
}