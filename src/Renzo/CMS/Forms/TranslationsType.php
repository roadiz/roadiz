<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file TranslationsType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Translation selector form field type.
 */
class TranslationsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
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
        return 'translations';
    }
}