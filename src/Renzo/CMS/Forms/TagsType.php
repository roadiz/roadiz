<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file TagsType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Tag form field type.
 */
class TagsType extends AbstractType
{
    protected $tags;

    /**
     * {@inheritdoc}
     */
    public function __construct($tags = null)
    {
        $this->tags = $tags;
    }

    /**
     * Set every tags s default choices values.
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $tags = Kernel::getService('em')
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
        return 'tags';
    }
}
