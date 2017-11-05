<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
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
 * @file NodeSourceJoinType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Doctrine\ORM\Proxy\Proxy;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Yaml\Yaml;

class NodeSourceJoinType extends AbstractNodeSourceFieldType
{
    /**
     * @var string
     */
    private $classname;

    /**
     * @var string
     */
    private $displayableMethod;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['nodeTypeField']->getType() === NodeTypeField::MANY_TO_MANY_T ||
            $options['nodeTypeField']->getType() === NodeTypeField::MANY_TO_ONE_T) {
            $configuration = Yaml::parse($options['nodeTypeField']->getDefaultValues());
            $this->classname = $configuration['classname'];
            $this->displayableMethod = $configuration['displayable'];
        }

        $builder->addModelTransformer(new CallbackTransformer(
            function ($entitiesToForm) use ($options) {
                /*
                 * If model is already an AbstractEntity
                 */
                if (!empty($entitiesToForm) &&
                    $entitiesToForm instanceof AbstractEntity) {
                    return $entitiesToForm->getId();
                } /*
                 * If model is a collection of AbstractEntity
                 */
                elseif (!empty($entitiesToForm) && is_array($entitiesToForm)) {
                    $idArray = [];
                    foreach ($entitiesToForm as $entity) {
                        if ($entity instanceof AbstractEntity) {
                            $idArray[] = $entity->getId();
                        }
                    }
                    return $idArray;
                } elseif (!empty($entitiesToForm)) {
                    return $entitiesToForm;
                }
                return '';
            },
            function ($formToEntities) use ($options) {
                if ($options['nodeTypeField']->getType() === NodeTypeField::MANY_TO_MANY_T) {
                    return $options['entityManager']->getRepository($this->classname)->findBy([
                        'id' => $formToEntities,
                    ]);
                }
                if ($options['nodeTypeField']->getType() === NodeTypeField::MANY_TO_ONE_T) {
                    return $options['entityManager']->getRepository($this->classname)->findOneBy([
                        'id' => $formToEntities,
                    ]);
                }
                return null;
            }
        ));
    }

    /**
     * Pass data to form twig template.
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $displayableData = [];

        $entities = call_user_func([$options['nodeSource'], $options['nodeTypeField']->getGetterName()]);

        if ($entities instanceof \Traversable) {
            /** @var AbstractEntity $entity */
            foreach ($entities as $entity) {
                if ($entity instanceof Proxy) {
                    $entity->__load();
                }
                $data = [
                    'id' => $entity->getId(),
                    'classname' => $this->classname,
                ];
                if (is_callable([$entity, $this->displayableMethod])) {
                    $data['name'] = call_user_func([$entity, $this->displayableMethod]);
                }
                $displayableData[] = $data;
            }
        } elseif ($entities instanceof AbstractEntity) {
            if ($entities instanceof Proxy) {
                $entities->__load();
            }
            $data = [
                'id' => $entities->getId(),
                'classname' => $this->classname,
            ];
            if (is_callable([$entities, $this->displayableMethod])) {
                $data['name'] = call_user_func([$entities, $this->displayableMethod]);
            }
            $displayableData[] = $data;
        }

        $view->vars['data'] = $displayableData;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'join';
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'join';
    }
}
