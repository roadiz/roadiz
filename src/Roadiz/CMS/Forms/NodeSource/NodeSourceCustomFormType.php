<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file NodeSourceCustomFormType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NodeSourceCustomFormType
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
class NodeSourceCustomFormType extends AbstractNodeSourceFieldType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'onPreSetData']
        )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                [$this, 'onPostSubmit']
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'required' => false,
            'mapped' => false,
            'class' => CustomForm::class,
            'multiple' => true,
            'property' => 'id',
        ]);

        $resolver->setRequired('nodeHandler');
        $resolver->setAllowedTypes('nodeHandler', [NodeHandler::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'custom_forms';
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

        /** @var NodesSources $nodeSource */
        $nodeSource = $event->getForm()->getConfig()->getOption('nodeSource');

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');

        $event->setData($entityManager
            ->getRepository(CustomForm::class)
            ->findByNodeAndField($nodeSource->getNode(), $nodeTypeField));
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var NodesSources $nodeSource */
        $nodeSource = $event->getForm()->getConfig()->getOption('nodeSource');

        /** @var EntityManager $entityManager */
        $entityManager = $event->getForm()->getConfig()->getOption('entityManager');

        /** @var NodeHandler $nodeHandler */
        $nodeHandler = $event->getForm()->getConfig()->getOption('nodeHandler');

        /** @var NodeTypeField $nodeTypeField */
        $nodeTypeField = $event->getForm()->getConfig()->getOption('nodeTypeField');

        $nodeHandler->setNode($nodeSource->getNode());
        $nodeHandler->cleanCustomFormsFromField($nodeTypeField, false);

        if (is_array($event->getData())) {
            $position = 0;
            foreach ($event->getData() as $customFormId) {
                /** @var CustomForm|null $tempCForm */
                $tempCForm = $entityManager->find(CustomForm::class, (int) $customFormId);

                if ($tempCForm !== null) {
                    $nodeHandler->addCustomFormForField($tempCForm, $nodeTypeField, false, $position);
                    $position++;
                } else {
                    throw new \RuntimeException('Custom form #'.$customFormId.' was not found during relationship creation.');
                }
            }
        }
    }
}
