<?php 


namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
* 
*/
class UsersType extends AbstractType
{
    protected $users;

    public function __construct ($users = null) {
        $this->users = $users;
    }

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {	
    	$users = Kernel::getInstance()->em()
    		->getRepository('RZ\Renzo\Core\Entities\User')
    		->findAll();

    	$choices = array();
        foreach ($users as $user) {
            if (!$this->users->contains($user)) {
                $choices[$user->getId()] = $user->getUserName();
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
        return 'users';
    }
}