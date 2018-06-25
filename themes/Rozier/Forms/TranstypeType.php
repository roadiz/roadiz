<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file TranstypeType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class TranstypeType
 * @package Themes\Rozier\Forms
 */
class TranstypeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'nodeTypeId',
            'choice',
            [
                'choices_as_values' => true,
                'choices' => $this->getAvailableTypes($options['em'], $options['currentType']),
                'label' => 'nodeType',
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'transtype';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
            'nodeName' => null,
            'attr' => [
                'class' => 'uk-form transtype-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
            'currentType',
        ]);

        $resolver->setAllowedTypes('em', ObjectManager::class);
        $resolver->setAllowedTypes('currentType', NodeType::class);
    }

    /**
     * @param EntityManager $em
     * @param NodeType $currentType
     * @return array
     */
    protected function getAvailableTypes(EntityManager $em, NodeType $currentType)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('n')
           ->from(NodeType::class, 'n')
           ->where($qb->expr()->neq('n.id', $currentType->getId()))
           ->orderBy('n.displayName', 'ASC');

        try {
            $types = $qb->getQuery()->getResult();

            $choices = [];
            /** @var NodeType $type */
            foreach ($types as $type) {
                $choices[$type->getDisplayName()] = $type->getId();
            }

            return $choices;
        } catch (NoResultException $e) {
            return [];
        }
    }
}
