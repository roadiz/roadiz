<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractNodeSourceFieldType extends AbstractType
{
    /**
     * Pass nodeSource to form twig template.
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['nodeSource'] = $options['nodeSource'];
        $view->vars['nodeTypeField'] = $options['nodeTypeField'];
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([
            'nodeSource',
            'nodeTypeField',
            'entityManager',
        ]);

        $resolver->setAllowedTypes('nodeSource', [NodesSources::class]);
        $resolver->setAllowedTypes('nodeTypeField', [NodeTypeField::class]);
        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
    }


    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }
}
