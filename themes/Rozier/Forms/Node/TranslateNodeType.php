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
 * @file TranslateNodeType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms\Node;

use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Forms\DataTransformer\TranslationTransformer;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TranslateNodeType
 * @package Themes\Rozier\Forms\Node
 */
class TranslateNodeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ObjectManager $em */
        $em = $options['em'];
        $translations = $em->getRepository('RZ\Roadiz\Core\Entities\Translation')
                           ->setDisplayingNotPublishedNodes(true)
                           ->findUnavailableTranslationsForNode($options['node']);


        $choices = [];

        /** @var Translation $translation */
        foreach ($translations as $translation) {
            $choices[$translation->getName()] = $translation->getId();
        }

        $builder->add('translation', 'choice', [
            'label' => 'translation',
            'choices' => $choices,
            'choices_as_values' => true,
            'required' => true,
            'multiple' => false,
        ])
        ->add('translate_offspring', 'checkbox', [
            'label' => 'translate_offspring',
            'required' => false,
        ]);

        $builder->get('translation')
            ->addModelTransformer(new TranslationTransformer($options['em']));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'translate_node';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'attr' => [
                'class' => 'uk-form node-translation-form',
            ],
        ]);

        $resolver->setRequired([
            'node',
            'em',
        ]);

        $resolver->setAllowedTypes('node', 'RZ\Roadiz\Core\Entities\Node');
        $resolver->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager');
    }
}
