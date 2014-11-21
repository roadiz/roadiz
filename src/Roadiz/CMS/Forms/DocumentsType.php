<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file DocumentsType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Document selector and uploader form field type.
 */
class DocumentsType extends AbstractType
{
    protected $selectedDocuments;

    /**
     * {@inheritdoc}
     *
     * @param array $documents Array of Document instances
     */
    public function __construct(array $documents)
    {
        $this->selectedDocuments = $documents;
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $callback = function ($object, ExecutionContextInterface $context) {

            $document = Kernel::getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Document', (int) $object);

            // Vérifie si le nom est bidon
            if (null !== $object && null === $document) {
                $context->addViolationAt(
                    null,
                    'Document '.$object.' does not exists',
                    array(),
                    null
                );
            }
        };

        $resolver->setDefaults(array(
            'class' => '\RZ\Roadiz\Core\Entities\Document',
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
         * Inject data as plain documents entities
         */
        $view->vars['data'] = $this->selectedDocuments;
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
        return 'documents';
    }
}
