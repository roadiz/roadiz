<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 *
 * @file NodeTreeType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Themes\Rozier\Widgets\NodeTreeWidget;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

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
            $this->nodeSource->getNode(),
            $this->nodeSource->getTranslation()
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
                                      ->findBy(['name' => $defaultValues]);

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
