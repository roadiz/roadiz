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
class TagsType extends AbstractType
{
    protected $tags;

    public function __construct ($tags = null) {

        $this->tags = $tags;
    }

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {	
    	$tags = Kernel::getInstance()->em()
    		->getRepository('RZ\Renzo\Core\Entities\Tag')
    		->findAllWithDefaultTranslation();

    	$choices = array();
        foreach ($tags as $tag) {
            if (!$this->tags->contains($tag)) {
                $choices[$tag->getId()] = $tag->getTranslatedTags()->first()->getName();
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
        return 'tags';
    }
}