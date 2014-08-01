<?php 


namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
* 
*/
class RolesType extends AbstractType
{
	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {	
    	$roles = Kernel::getInstance()->em()
    		->getRepository('RZ\Renzo\Core\Entities\Role')
    		->findAll();

    	$choices = array();
    	foreach ($roles as $role) {
    		$choices[$role->getId()] = $role->getName();
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
        return 'roles';
    }
}