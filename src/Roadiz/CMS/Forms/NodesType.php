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

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Node selector and uploader form field type.
 */
class NodesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(function ($mixedEntities) {
            if ($mixedEntities instanceof Collection) {
                return $mixedEntities->toArray();
            }
            if (!is_array($mixedEntities)) {
                return [$mixedEntities];
            }
            return $mixedEntities;
        }, function ($mixedIds) use ($options) {
            /** @var NodeRepository $repository */
            $repository = $options['entityManager']
                ->getRepository(Node::class)
                ->setDisplayingAllNodesStatuses(true);
            if (is_array($mixedIds) && count($mixedIds) === 0) {
                return [];
            } elseif (is_array($mixedIds) && count($mixedIds) > 0) {
                if ($options['multiple'] === false) {
                    return $repository->findOneBy(['id' => $mixedIds]);
                }
                return $repository->findBy(['id' => $mixedIds]);
            } elseif ($options['multiple'] === true) {
                return [];
            } else {
                return $repository->findOneById($mixedIds);
            }
        }));
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Node::class,
            'multiple' => true,
            'property' => 'id',
            'nodes' => [],
        ]);

        $resolver->setRequired([
            'entityManager'
        ]);
        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
        $resolver->setAllowedTypes('multiple', ['boolean']);
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
        if (!empty($options['nodes'])) {
            $view->vars['data'] = $options['nodes'];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'nodes';
    }
}
