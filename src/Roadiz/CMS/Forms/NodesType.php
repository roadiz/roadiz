<?php
/**
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
 * @file NodesType.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $callback = function ($object, ExecutionContextInterface $context) {

            if (is_array($object)) {
                $nodes = Kernel::getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\Node')
                                ->findBy(['id'=>$object]);

                foreach (array_values($object) as $key => $value) {
                    // Vérifie si le nom est bidon
                    if (null !== $value && null === $nodes[$key]) {
                        $context->addViolationAt(
                            null,
                            'Node #'.$value.' does not exists',
                            [],
                            null
                        );
                    }
                }

            } else {
                $node = Kernel::getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Node', (int) $object);

                // Vérifie si le nom est bidon
                if (null !== $object && null === $node) {
                    $context->addViolationAt(
                        null,
                        'Node '.$object.' does not exists',
                        [],
                        null
                    );
                }
            }
        };

        $resolver->setDefaults([
            'class' => '\RZ\Roadiz\Core\Entities\Node',
            'multiple' => true,
            'property' => 'id',
            'constraints' => [
                new Callback($callback)
            ]
        ]);
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
