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
 * @file NodeSourceDocumentType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NodeSourceDocumentType
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
class NodeSourceDocumentType extends AbstractType
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
     * @var Document[]
     */
    private $selectedDocuments;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * NodeSourceDocumentType constructor.
     * @param NodesSources $nodeSource
     * @param NodeTypeField $nodeTypeField
     * @param EntityManager $entityManager
     */
    public function __construct(NodesSources $nodeSource, NodeTypeField $nodeTypeField, EntityManager $entityManager)
    {
        $this->nodeSource = $nodeSource;
        $this->nodeTypeField = $nodeTypeField;
        $this->entityManager = $entityManager;
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
            'class' => '\RZ\Roadiz\Core\Entities\Document',
            'multiple' => true,
            'property' => 'id',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'documents';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $this->selectedDocuments = $this->nodeSource->getHandler()
            ->getDocumentsFromFieldName($this->nodeTypeField->getName());
        $event->setData($this->selectedDocuments);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $hdlr = $this->nodeSource->getHandler();
        $hdlr->cleanDocumentsFromField($this->nodeTypeField, false);

        if (is_array($event->getData())) {
            $position = 0;
            foreach ($event->getData() as $documentId) {
                $tempDoc = $this->entityManager
                    ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);
                if ($tempDoc !== null) {
                    $hdlr->addDocumentForField($tempDoc, $this->nodeTypeField, false, $position);
                    $position++;
                } else {
                    throw new \RuntimeException('Document #'.$documentId.' was not found during relationship creation.');
                }
            }
        }
    }
}
