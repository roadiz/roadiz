<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file NodeTreeType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Themes\Rozier\Widgets\NodeTreeWidget;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Node tree embedded type in a node source form.
 *
 * This form type is not published inside Roadiz CMS as it needs
 * NodeTreeWidget which is part of Rozier Theme.
 *
 */
class NodeTreeType extends AbstractType
{
    protected $field;
    protected $nodeSource;
    protected $controller;

    /**
     * {@inheritdoc}
     *
     * @param RZ\Roadiz\Core\Entities\NodesSources     $source
     * @param RZ\Roadiz\Core\Entities\NodeTypeField    $field
     * @param \RZ\Roadiz\CMS\Controllers\AppController $refereeController
     */
    public function __construct(
        NodesSources $source,
        NodeTypeField $field,
        $refereeController
    ) {
        $this->nodeSource = $source;
        $this->field = $field;
        $this->controller = $refereeController;

        if (NodeTypeField::CHILDREN_T !== $this->field->getType()) {
            throw new \RuntimeException("Given field is not a NodeTypeField::CHILDREN_T field.", 1);
        }
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
         * Inject data as plain documents entities
         */
        $view->vars['nodeTree'] = new NodeTreeWidget(
            $this->controller->getKernel()->getRequest(),
            $this->controller,
            $this->nodeSource->getNode()
        );

        /*
         * Linked types to create quick add buttons
         */
        $defaultValues = explode(',', $this->field->getDefaultValues());
        foreach ($defaultValues as $key => $value) {
            $defaultValues[$key] = trim($value);
        }

        $nodeTypes = $this->controller->getService('em')
                                      ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                                      ->findBy(array('name' => $defaultValues));

        $view->vars['linkedTypes'] = $nodeTypes;
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
        return 'childrennodes';
    }
}
