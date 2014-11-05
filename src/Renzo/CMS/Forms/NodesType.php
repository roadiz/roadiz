<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Forms;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Node selector and uploader form field type.
 */
class NodesType extends AbstractType
{
    protected $selectedNodes;

    /**
     * {@inheritdoc}
     *
     * @param array $nodes Array of Node instances
     */
    public function __construct(array $nodes)
    {
        $this->selectedNodes = $nodes;
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $callback = function ($object, ExecutionContextInterface $context) {

            $node = Kernel::getService('em')
                            ->find('RZ\Renzo\Core\Entities\Node', (int) $object);

            // Vérifie si le nom est bidon
            if (null !== $object && null === $node) {
                $context->addViolationAt(
                    null,
                    'Node '.$object.' does not exists',
                    array(),
                    null
                );
            }
        };

        $resolver->setDefaults(array(
            'class' => '\RZ\Renzo\Core\Entities\Node',
            'multiple' => true,
            'property' => 'id',
            'constraints' => array(
                new Callback($callback)
            )
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        /*
         * Inject data as plain nodes entities
         */
        $view->vars['data'] = $this->selectedNodes;
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'nodes';
    }
}
