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
 * @file RedirectionType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\Rozier\Forms;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class RedirectionType
 * @package Themes\Rozier\Forms
 */
class RedirectionType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * RedirectionType constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('query', 'text', [
            'label' => 'redirection.query',
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('redirectUri', 'text', [
            'label' => 'redirection.redirect_uri',
            'required' => false,
        ])
        ->add('type', 'choice', [
            'label' => 'redirection.type',
            'choices_as_values' => true,
            'choices' => [
                'redirection.moved_permanently' => Response::HTTP_MOVED_PERMANENTLY,
                'redirection.moved_temporarily' => Response::HTTP_FOUND,
            ]
        ]);
    }

    public function getName()
    {
        return 'redirection';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'RZ\Roadiz\Core\Entities\Redirection',
            'attr' => [
                'class' => 'uk-form redirection-form',
            ],
            'constraints' => [
                new UniqueEntity([
                    'fields' => 'query',
                    'entityManager' => $this->entityManager
                ])
            ]
        ]);
    }
}
