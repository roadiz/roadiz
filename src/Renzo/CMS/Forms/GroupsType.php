<?php 


namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
* 
*/
class GroupsType extends AbstractType
{
    protected $groups;

    public function __construct ($groups = null) {

        $this->groups = $groups;
    }

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {	
    	$groups = Kernel::getInstance()->em()
    		->getRepository('RZ\Renzo\Core\Entities\Group')
    		->findAll();

    	$choices = array();
    	foreach ($groups as $group) {
            if (!$this->groups->contains($group)) {
                $choices[$group->getId()] = $group->getName();
            }
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
        return 'groups';
    }
}