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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NodeSourceCustomFormType
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
class NodeSourceCustomFormType extends AbstractType
{
    /**
     * @var NodesSources
     */
    private $nodeSource;

    /**
     * @var NodeTypeField
     */
    private $nodeTypeField;

    /**
     * @var CustomForm[]
     */
    private $selectedCustomForms;

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var NodeHandler
     */
    private $nodeHandler;

    /**
     * NodeSourceDocumentType constructor.
     * @param NodesSources $nodeSource
     * @param NodeTypeField $nodeTypeField
     * @param EntityManager $entityManager
     * @param NodeHandler $nodeHandler
     */
    public function __construct(
        NodesSources $nodeSource,
        NodeTypeField $nodeTypeField,
        EntityManager $entityManager,
        NodeHandler $nodeHandler
    ) {
        $this->nodeSource = $nodeSource;
        $this->nodeTypeField = $nodeTypeField;
        $this->entityManager = $entityManager;
        $this->nodeHandler = $nodeHandler;
        $this->nodeHandler->setNode($this->nodeSource->getNode());
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            array($this, 'onPreSetData')
        )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                array($this, 'onPostSubmit')
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => $this->nodeTypeField->getLabel(),
            'required' => false,
            'mapped' => false,
            'class' => CustomForm::class,
            'multiple' => true,
            'property' => 'id',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'custom_forms';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $this->selectedCustomForms = $this->entityManager
            ->getRepository(CustomForm::class)
            ->findByNodeAndFieldName($this->nodeSource->getNode(), $this->nodeTypeField->getName());
        $event->setData($this->selectedCustomForms);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $this->nodeHandler->cleanCustomFormsFromField($this->nodeTypeField, false);

        if (is_array($event->getData())) {
            $position = 0;
            foreach ($event->getData() as $customFormId) {
                /** @var CustomForm|null $tempCForm */
                $tempCForm = $this->entityManager
                    ->find(CustomForm::class, (int) $customFormId);
                if ($tempCForm !== null) {
                    $this->nodeHandler->addCustomFormForField($tempCForm, $this->nodeTypeField, false, $position);
                    $position++;
                } else {
                    throw new \RuntimeException('Custom form #'.$customFormId.' was not found during relationship creation.');
                }
            }
        }
    }
}
